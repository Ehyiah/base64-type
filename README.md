# base64-type to use in Symfony framework

## Installation
### Step 1 Require bundle
```sh
  composer require ehyiah/base64-type
```
It's done, you can use the Base64FileType to build a QuillJs WYSIWYG

This bundle will transform a base64Encoded file into a Symfony\Component\HttpFoundation\File\UploadedFile that can be handled natively by Symfony.

- Your entity must implement Bas64MediaInterface to ensure needed methos are prensent
otherwise your file will have a random name.
- you must send your base64 encoded file in a json to be handled by Symfony
```
{
    ///// my fields
    my-input-name: {
        "displayName": "my image name.jpg"
        "file": "base64_encoded_file........"
    }
}
```

It can of course be nested like any other classic fields.

and that's it.
