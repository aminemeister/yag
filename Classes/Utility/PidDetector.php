<?php
/***************************************************************
* Copyright notice
*
*   2010 Daniel Lienert <daniel@lienert.cc>, Michael Knoll <mimi@kaktusteam.de>
* All rights reserved
*
*
* This script is part of the TYPO3 project. The TYPO3 project is
* free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* The GNU General Public License can be found at
* http://www.gnu.org/copyleft/gpl.html.
*
* This script is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Pid detector class for getting storage PID informations.
 *
 * PID detector returns storage PIDs for records depending on environment.
 * Currently there are 3 different environments:
 *
 * 1. Frontend - We get PID settings from Content Element and from TypoScript / Flexform which are both merged into settings
 * 2. Backend
 * 2.1 Yag module - We get PID from currently selected page / pid in page tree
 * 2.2 Content Element - User has selected PID in selector
 *     TODO The source selector needs to be extended by a column "PID / PAGE" on which pages are
 *     TODO displayed which contain yag gallery records and the user is allowed to see respecting
 *     TODO mount points / access rights
 *
 * Furthermore, pid detector must be able to return PIDs of pages that user is enabled to see and
 * contains yag gallery items
 *
 * @package Utility
 * @author Michael Knoll
 */
class Tx_Yag_Utility_PidDetector {

    /**
     * Holds singleton instance of this object
     *
     * @var Tx_Yag_Utility_PidDetector
     */
    private static $instance = null;



    /**
     * Returns singleton instance of this object
     *
     * @static
     * @param $mode If no mode is given, mode is detected by this method
     * @return Tx_Yag_Utility_PidDetector
     */
    public static function getInstance($mode = null) {
        if (self::$instance === null) {
            if ($mode === null) {
                self::$instance = new Tx_Yag_Utility_PidDetector(self::getExtensionMode());
            } else {
                self::$instance = new Tx_Yag_Utility_PidDetector($mode);
            } /* @var $instance Tx_Yag_Utility_PidDetector */

            $objectManager = t3lib_div::makeInstance('Tx_Extbase_Object_ObjectManager'); /* @var $objectManager Tx_Extbase_Object_ObjectManager */
            self::$instance->injectConfigurationManager($objectManager->get('Tx_Extbase_Configuration_ConfigurationManagerInterface'));
        }
        return self::$instance;
    }



    /**
     * Returns pidDetector mode for current extension usage
     *
     * @return string
     */
    public static function getExtensionMode() {
        if (TYPO3_MODE === 'BE') {
            if (user_Tx_Yag_Utility_Flexform_RecordSelector::$flexformMode) {
                // Record selector is activated => we are in flexform mode
                return Tx_Yag_Utility_PidDetector::BE_CONTENT_ELEMENT_MODE;
            } else {
                return Tx_Yag_Utility_PidDetector::BE_YAG_MODULE_MODE;
            }
        } elseif (TYPO3_MODE === 'FE') {
            return Tx_Yag_Utility_PidDetector::FE_MODE;
        }
    }



    /**
     * This method is for testing only.
     *
     * TODO think about better way to implement this
     *
     * @static
     */
    public static function resetSingleton() {
        self::$instance = null;
    }



	/**
	 * Define some constants to set mode of detector
	 */
	const FE_MODE = 'fe_mode';
	const BE_YAG_MODULE_MODE = 'be_yag_module_mode';
	const BE_CONTENT_ELEMENT_MODE = 'be_content_element_mode';



    /**
     * Holds array of allowed modes
     *
     * @var array
     */
    protected static $allowedModes = array(self::FE_MODE, self::BE_CONTENT_ELEMENT_MODE, self::BE_YAG_MODULE_MODE);



	/**
	 * Holds mode for pid detector
	 *
	 * @var string
	 */
	protected $mode;



    /**
     * Holds instance of extbase configuration manager
     *
     * @var Tx_Extbase_Configuration_ConfigurationManagerInterface
     */
    protected $configurationManager;



	/**
	 * Constructor for pid detector.
	 *
	 * Creates new pid detector for given mode.
	 *
	 * @throws Exception If $mode is not allowed
	 * @param string $mode Set mode of pid detector
	 */
	protected function __construct($mode) {
		if (!$this->modeIsAllowed($mode)) {
			throw new Exception('$mode is not allowed: ' . $mode . ' 1321464415');
		}
		$this->mode = $mode;
	}



    /**
     * Injects configuration manager
     *
     * @param Tx_Extbase_Configuration_ConfigurationManagerInterface $configurationManager
     */
    public function injectConfigurationManager(Tx_Extbase_Configuration_ConfigurationManagerInterface $configurationManager) {
        $this->configurationManager = $configurationManager;
    }



	/**
	 * Returns mode of pid detector
	 * 
	 * @return string
	 */
	public function getMode() {
		return $this->mode;
	}



	/**
	 * Returns true, if mode is allowed
	 *
	 * @param bool $mode Mode to be checked
	 * @return bool True, if mode is allowed
	 */
	protected function modeIsAllowed($mode) {
		return in_array($mode, self::$allowedModes);
	}



    public function getPids() {
        $pids = array();
        switch ($this->mode) {
            case self::FE_MODE :
                $pids = $this->getPidsInFeMode();
            break;

            case self::BE_CONTENT_ELEMENT_MODE :

            break;

            case self::BE_YAG_MODULE_MODE :
                $pids = $this->getPidsInBeModuleMode();
            break;

        }
        return $pids;
    }



    protected function getPidsInFeMode() {

        /**
         * Where do we get PIDs from, if we are in frontend mode?
         *
         * If we are in FE mode, we get PIDs from setting in Flexform. There we
         * select a PID and some yag objects within this pid.
         */
        $configuration = $this->configurationManager->getConfiguration(Tx_Extbase_Configuration_ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS);
        // TODO this has to be set in source-selector
        $configuration['selectedPid'] = 999;
        $selectedPid = $configuration['selectedPid'];

        return array($selectedPid);

    }



    protected function getPidsInBeModuleMode() {

        /**
         * Where do we get PIDs if we are in BE module mode?
         *
         * To enable BE module, we have to select a pid from page tree. This pid
         * is available from GP vars. If we do not have GP var, something went wrong!
         */
        $pageId = (integer)t3lib_div::_GP('id');
        if ($pageId > 0) {
            return array($pageId);
        } else {
            throw new Exception('Backend module of yag had been called without a page ID! 1327105602');
        }

    }



    protected function getPidsInContentElementMode() {

        /**
         * If we are in content element mode, we have to get all PIDs that currently logged in
         * user is allowed to see.
         */
        

    }

}
?>