<?php

namespace Drupal\wmdummy_data\Faker\Provider;

use Faker\Provider\Base;

/**
 * @property string vimeoUrl
 * @property string vimeoId
 */
class VimeoVideo extends Base
{
    /** @var string[] */
    protected static $ids = ['22439234', '62092214', '9479342', '31005812', '23237102', '29950141', '56298775', '6428069', '4749536', '54348266', '3365942', '32863936', '64122803', '101358524', '14821961', '32958521', '11386048', '23079092', '50672419', '12112529', '70573323', '73873609', '8951807', '47224216', '7199178', '29186408', '8569187', '27268591', '4240369', '29568236', '6223439', '7256322', '6132324', '2884813', '62980495', '4729762', '10570139', '73317780', '36092192', '8918647', '72764432', '7231932', '49671213', '6736261', '7703592', '9175212', '2910103', '31041703', '7688147', '3744985', '3911557', '8967457', '6932347', '942978', '2521215', '39434113', '3941280', '24314714', '9078364', '3551875', '28715228', '10022953', '28885242', '6409259', '3846698', '8572290', '28531048', '30233604', '7563705', '6356422', '16474921', '1004092', '9235525', '7853947', '7566422', '9316949', '4815813', '16660444', '14431677', '7286652', '12317623', '5988036', '23282730', '2910853'];

    public function vimeoUrl(): string
    {
        return sprintf('https://vimeo.com/%s', $this->vimeoId());
    }

    public function vimeoId()
    {
        return self::randomElement(static::$ids);
    }
}
