<?php

/* For licensing terms, see /license.txt */
/**
 * Uninstall the plugin
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com
 * @package chamilo.plugin.externalPageNGL
 */
require_once dirname(__FILE__) . '/config.php';
ExternalPageNGLPlugin::create()->uninstall();