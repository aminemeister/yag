<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Michael Knoll <mimi@kaktusteam.de>
*           Daniel Lienert <daniel@lienert.cc>
*           
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Controller Development, generating some sample content for gallery
 *
 * @package Controller
 * @author Michael Knoll <mimi@kaktusteam.de>
 */
class Tx_Yag_Controller_DevelopmentController extends Tx_Yag_Controller_AbstractController {
	
	/**
	 * Gallery Repository
	 *
	 * @var Tx_Yag_Domain_Repository_GalleryRepository
	 */
	protected $galleryRepository;
	
	
	
	/**
	 * Album repository
	 *
	 * @var Tx_Yag_Domain_Repository_AlbumRepository
	 */
	protected $albumRepository;
	
	
	
	/**
	 * Item repository
	 *
	 * @var Tx_Yag_Domain_Repository_ItemRepository
	 */
	protected $itemRepository;
	
	
	
	/**
	 * Holds an instance of resolution file cache repository
	 *
	 * @var Tx_Yag_Domain_Repository_ResolutionFileCacheRepository
	 */
	protected $resolutionFileCacheRepository;
	
	
	
    /**
     * Initializes the current action
     *
     * @return void
     */
    protected function initializeAction() {
    	$this->resolutionFileCacheRepository = t3lib_div::makeInstance('Tx_Yag_Domain_Repository_ResolutionFileCacheRepository');
    	$this->galleryRepository = t3lib_div::makeInstance('Tx_Yag_Domain_Repository_GalleryRepository');
        $this->albumRepository = t3lib_div::makeInstance('Tx_Yag_Domain_Repository_AlbumRepository');
        $this->itemRepository = t3lib_div::makeInstance('Tx_Yag_Domain_Repository_ItemRepository');
    }
    
    
	
	/**
	 * Creates sample data like resolutions, albums etc. to start working with
	 */
	public function createSampleDataAction() {

		// Add gallery
		$gallery = new Tx_Yag_Domain_Model_Gallery();
		$gallery->setDescription('Description for first gallery');
		$gallery->setName('First Gallery');
		
		// Add album
		$album = new Tx_Yag_Domain_Model_Album();
		$album->addGallery($gallery);
		$gallery->addAlbum($album);
		
		$album->setName('Sample Album');
		$album->setDescription('This is a sample album with some sweet sample images.');
		
		// Persist stuff
		$this->galleryRepository->add($gallery);
		
		// Create item files and items
		for ($i = 1; $i < 10; $i++) {
			// Create item and add item files
			$item = new Tx_Yag_Domain_Model_Item();
			$item->setDescription('Description for photo ' . $i);
			$item->setTitle('Photo ' . $i);
			
			// Create an resolution file cache entries
			$singleItemFile = new Tx_Yag_Domain_Model_ResolutionFileCache($item, 
			    'typo3conf/ext/yag/Resources/Public/Samples/demo_800_600-00' . $i . '.jpg',
			    800, 600, 80
			);
			$thumbItemFile = new Tx_Yag_Domain_Model_ResolutionFileCache($item, 
                'typo3conf/ext/yag/Resources/Public/Samples/demo_80_60-00' . $i . '.jpg',
                80, 60, 80
            );
            
			// add item to album
			$album->addItem($item);
			
			// Persist stuff
			$this->resolutionFileCacheRepository->add($singleItemFile);
			$this->resolutionFileCacheRepository->add($thumbItemFile);
			$this->itemRepository->add($item);
		}
		
		// Persist album
		$this->albumRepository->add($album);
		
	}
	
	
	/**
	 * Empties all tables of gallery plugin
	 * 
	 * @return string The rendered delete all action
	 */
	public function deleteAllAction() {
		$query = $this->albumRepository->createQuery();
        $query->statement('TRUNCATE TABLE tx_yag_album_gallery_mm')->execute();
        $query->statement('TRUNCATE TABLE tx_yag_domain_model_album')->execute();
        $query->statement('TRUNCATE TABLE tx_yag_domain_model_gallery')->execute();
        $query->statement('TRUNCATE TABLE tx_yag_domain_model_item')->execute();
        $query->statement('TRUNCATE TABLE tx_yag_gallery_album_mm')->execute();
        $query->statement('TRUNCATE TABLE tx_yag_gallery_resolution_file_cache')->execute();
	}

}
?>