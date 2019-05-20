<?php

namespace Drupal\wmdummy_data\Faker\Provider;

use Faker\Provider\Base;

/**
 * @property string $youTubeUrl
 * @property string $shortYouTubeUrl
 * @property string $youTubeId
 */
class YouTubeVideo extends Base
{
    protected static $ids = ['nfWlot6h_JM', 'CevxZvSJLk8', '09R8_2nJtjg', 'fRh_vgS2dFE', '9bZkp7q19f0', 'KYniUCGPGLs', 'OPf0YbXqDm0', 'RgKAFK5djSk', 'JGwWNGJdvx8', 'kJQP7kiw5Fk', '0KSOMA3QBU0', 'YqeW9_5kURI', 'NUsoVlDFqZg'];

    public function youTubeUrl()
    {
        return sprintf('https://www.youtube.com/watch?v=%s', $this->youTubeId());
    }

    public function shortYouTubeUrl()
    {
        return sprintf('https://youtu.be/%s', $this->youTubeId());
    }

    public function youTubeId()
    {
        return self::randomElement(static::$ids);
    }
}
