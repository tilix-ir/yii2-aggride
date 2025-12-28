1. Create folder `yii2-aggrid`
2. Copy all files above into the folder structure
3. Upload to GitHub
4. Publish to Packagist OR use locally:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../yii2-aggrid"
        }
    ],
    "require": {
        "yourvendor/yii2-aggrid": "@dev"
    }
}
```

5. Run: `composer update yourvendor/yii2-aggrid`

Done! 🎉