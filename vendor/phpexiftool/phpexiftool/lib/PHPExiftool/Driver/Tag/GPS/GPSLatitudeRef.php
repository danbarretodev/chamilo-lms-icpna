<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\GPS;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class GPSLatitudeRef extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'GPSLatitudeRef';

    protected $FullName = 'mixed';

    protected $GroupName = 'GPS';

    protected $g0 = 'mixed';

    protected $g1 = 'mixed';

    protected $g2 = 'mixed';

    protected $Type = 'string';

    protected $Writable = false;

    protected $Description = 'GPS Latitude Ref';

    protected $Values = array(
        'N' => array(
            'Id' => 'N',
            'Label' => 'North',
        ),
        'S' => array(
            'Id' => 'S',
            'Label' => 'South',
        ),
    );

    protected $local_g1 = 'mixed';

    protected $local_g2 = 'mixed';

    protected $flag_Permanent = 'mixed';

    protected $MaxLength = 'mixed';

}
