<?php

namespace Ehyiah\Base64Type;

use Interfaces\Base64MediaInterface;
use LogicException;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ApiBase64FileType extends AbstractType implements DataMapperInterface
{
    public function __construct(
        private readonly ValidatorInterface $validator,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('displayName', TextType::class, ['required' => true])
            ->add('file', TextType::class)
            ->setDataMapper($this)
        ;
    }

    public function mapDataToForms(mixed $viewData, \Traversable $forms): void
    {
    }

    public function mapFormsToData(\Traversable $forms, mixed &$viewData): void
    {
        if (null === $viewData) {
            return;
        }

        if (!$viewData instanceof Base64MediaInterface) {
            throw new LogicException('please provide data_class option in order to use ' . self::class);
        }

        /** @var FormInterface[] $forms */
        $forms = iterator_to_array($forms);
        $displayNameForm = $forms['displayName'];
        $fileForm = $forms['file'];
        $base64 = $fileForm->getData();

        if (null === $base64) {
            return;
        }

        $base64Parts = explode(';base64,', $base64);
        if (!isset($base64Parts[1])) {
            throw new LogicException('malformed base64 file');
        }
        $base64File = base64_decode($base64Parts[1], true);

        $file = tempnam(sys_get_temp_dir(), 'file-uploaded');

        if (is_string($file)) {
            file_put_contents($file, $base64File);
            $mimeType = MimeTypes::getDefault()->guessMimeType($file);
            $file = new UploadedFile($file, $displayNameForm->getData(), $mimeType, null, true);
        }

        if (method_exists($viewData, 'setFile')) {
            $viewData->setFile($file);

            $groups = [];
            $rootValidationGroups = $fileForm->getRoot()->getConfig()->getOptions()['validation_groups'];
            $apiBase64ValidationGroups = $fileForm->getParent()->getConfig()->getOptions()['validation_groups'];
            $fileFormValidationGroups = $fileForm->getConfig()->getOptions()['validation_groups'];
            if (is_array($rootValidationGroups)) {
                $groups = array_merge($rootValidationGroups, $groups);
            }
            if (is_array($apiBase64ValidationGroups)) {
                $groups = array_merge($apiBase64ValidationGroups, $groups);
            }
            if (is_array($fileFormValidationGroups)) {
                $groups = array_merge($fileFormValidationGroups, $groups);
            }

            $errors = $this->validator->validateProperty($viewData, 'file', $groups);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $formError = new FormError(
                        $error->getMessage(), $error->getMessageTemplate()
                    );
                    $fileForm->addError($formError);
                }

                $viewData->setFile(null);
            }

            $viewData->setDisplayName($displayNameForm->getData());
            if (isset($mimeType)) {
                $viewData->setMimeType($mimeType);
            }
        }
    }
}
