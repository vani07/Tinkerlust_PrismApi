<?php  

class Tinkerlust_PrismApi_ProductController extends Mage_Core_Controller_Front_Action
{	
	public function _construct(){
		$this->helper = Mage::helper('prismapi');
	}

	public function createAction(){
		$input_data = json_decode(file_get_contents('php://input'), true);
		$term = $input_data['must'][0]['query_string']['query'];
		$product_per_page = $input_data['size'];		
		// print_r($term);
		$query = Mage::getModel('catalogsearch/query')->setQueryText($term)->prepare();
	    $fulltextResource = Mage::getResourceModel('catalogsearch/fulltext')->prepareResult(
	        Mage::getModel('catalogsearch/fulltext'),
	        $term,
	        $query
	    );

	    $collection = Mage::getResourceModel('catalog/product_collection')->setPageSize($product_per_page);
	    $collection->addAttributeToSelect('*');
	    $collection->getSelect()->joinInner(
	        array('search_result' => $collection->getTable('catalogsearch/result')),
	        $collection->getConnection()->quoteInto(
	            'search_result.product_id=e.entity_id AND search_result.query_id=?',
	            $query->getId()
	        ),
	        array('relevance' => 'relevance')
	    );

	    $data = [];
	    $data['total'] = $collection->getSize();
	    $data['current_page'] = 1;

	    if ($data['total'] > ( ($data['current_page']-1) * $product_per_page ) ){
	    	$data['results'] = [];

	    	$collection->setCurPage($data['current_page']);

	    	foreach($collection as $product){
	    		// $thisProduct = $product->getData();
	    		$checkSalable = $product->getData('is_salable');
	    		if ($checkSalable != 0) {
		    		$thisProduct['id'] 			= $product->getData('entity_id');
		    		$thisProduct['name'] 		= $product->getData('name');
		    		
		    		$thisProduct['description'] = "<a href='".Mage::getBaseUrl().$product->getData('url_key')."'>More Details</a>";
		    		
		    		$thisProduct['image_urls'] 	= Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product'.$product->getData('image');
		    		
		    		$thisProduct['stock'] 		= (int)Mage::getModel('cataloginventory/stock_item')
                				->loadByProduct($product)->getQty();
		    		
		    		$thisProduct['price'] 		= $product->getData('special_price') == null ? $product->getData('price') : $product->getData('special_price');

		    		$thisProduct['currency_code']= 'IDR';

		    		$thisProduct['discount'] 	= null;  
		    		
		    		$data['results'][] 			= $thisProduct;
	    		}
	    	}
	    }
	    else {
	    	$data['results'] = array();
	    }
	    
	    // print_r($data);
	    // header('Content-Type: application/json');
	    // echo json_encode(["helo"=>"maybe"],JSON_UNESCAPED_UNICODE);
			$this->helper->buildJson($data,true);
		}
}