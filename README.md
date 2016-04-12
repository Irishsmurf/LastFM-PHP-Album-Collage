# LastFM PHP Album Collage
[![Build Status](https://travis-ci.org/Irishsmurf/LastFM-PHP-Album-Collage.svg)](https://travis-ci.org/Irishsmurf/LastFM-PHP-Album-Collage)
[![Code Climate](https://codeclimate.com/github/Irishsmurf/LastFM-PHP-Album-Collage/badges/gpa.svg)](https://codeclimate.com/github/Irishsmurf/LastFM-PHP-Album-Collage)
[![Issue Count](https://codeclimate.com/github/Irishsmurf/LastFM-PHP-Album-Collage/badges/issue_count.svg)](https://codeclimate.com/github/Irishsmurf/LastFM-PHP-Album-Collage)
[![Test Coverage](https://codeclimate.com/github/Irishsmurf/LastFM-PHP-Album-Collage/badges/coverage.svg)](https://codeclimate.com/github/Irishsmurf/LastFM-PHP-Album-Collage/coverage)


A Script that takes in `LastFM username` and collages their most played albums in a grid.
Uses S3 to store the images in a persistant manner without filling up the local disk.

Fully supported to run on AWS Elastic Beanstalk

---

**Tested using `PHP 5.5`, `PHP 7` and requires `PHP-GD library` for building the images.**

---

## Requirements

* **AWS IAM Roles** (running on your instance)
* **LastFM API Key**

### IAM Role Sample JSON:

```json
{
  "Statement": [
    {
      "Sid": "Stmt1402566164255",
      "Action": [
        "s3:GetObject",
        "s3:GetObjectVersion",
        "s3:ListBucket",
        "s3:PutObject",
        "s3:PutObjectAcl",
        "s3:PutObjectVersionAcl",
        "s3:DeleteObject"
      ],
      "Effect": "Allow",
      "Resource": [
        "arn:aws:s3:::<Bucket Name/*",
        "arn:aws:s3:::<Bucket Name>"
      ]
    }
  ]
}
```

### Last.FM API Key

Don't forget to fill you API Key in the `config.inc.php` file!

```php
$config['api_key'] = '<LastFM Key>'
```

If no `config.inc.php` file is found, it will use the following environment variables: `api_key` and `bucket`.

### Credits
Japanese TrueType font by lindwurm - https://github.com/Koruri/Koruri under Apache v2.0
