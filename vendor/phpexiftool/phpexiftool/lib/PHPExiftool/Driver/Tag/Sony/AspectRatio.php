<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Sony;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class AspectRatio extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'AspectRatio';

    protected $FullName = 'mixed';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Aspect Ratio';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 1,
            'Label' => '3:2',
        ),
        1 => array(
            'Id' => 2,
            'Label' => '16:9',
        ),
        2 => array(
            'Id' => 4,
            'Label' => '3:2',
        ),
        3 => array(
            'Id' => 8,
            'Label' => '16:9',
        ),
    );

}
