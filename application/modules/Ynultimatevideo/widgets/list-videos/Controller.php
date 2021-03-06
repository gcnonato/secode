<?php

/**
 * @category ynultimatevideo
 * @package widget
 * @subpackage search-manage-videos
 * @author dang tran
 */
class Ynultimatevideo_Widget_ListVideosController extends Engine_Content_Widget_Abstract {
    public function indexAction() {
        $settings = Engine_Api::_()->getApi('settings', 'core');


        $searchParams = $_GET;
        $params = $this -> _getAllParams();
        $p = Zend_Controller_Front::getInstance()->getRequest()->getParams();
        $params = array_merge($params, $p);
        unset($params['controller']);
        unset($params['name']);
        unset($params['action']);
        unset($params['module']);

        if (isset($params['category_id'])) {
            $category = Engine_Api::_()->getItem('ynultimatevideo_category', $params['category_id']);
            if ($category)
                $this->view->category = $category;
        }
        if (isset($params['category'])) {
            $categoryTbl = Engine_Api::_()->getItemTable('ynultimatevideo_category');
            $categorySelect = $categoryTbl->select()->where('option_id = ?', $params['category']);
            $category = $categoryTbl->fetchRow($categorySelect);
            if ($category)
                $this->view->category = $category;
        }
        $this -> view -> formValues = $params;
        $p_arr = array();
        foreach ($params as $k => $v) {
            $p_arr[] = $k;
            $p_arr[] = $v;
        }
        $params_str = implode('/', $p_arr);
        $this -> view -> params_str = $params_str;

        // view mode
        $mode_enabled = array();
        if(isset($params['mode_simple']) && $params['mode_simple'])
        {
            $mode_enabled[] = 'simple';
        }
        if(isset($params['mode_grid']) && $params['mode_grid'])
        {
            $mode_enabled[] = 'grid';
        }
        if(isset($params['mode_list']) && $params['mode_list'])
        {
            $mode_enabled[] = 'list';
        }
        if(isset($params['mode_casual']) && $params['mode_casual'])
        {
            $mode_enabled[] = 'casual';
        }
        if(isset($params['view_mode']) && in_array($params['view_mode'], $mode_enabled))
        {
            $view_mode = $params['view_mode'];
        } else if ($mode_enabled) {
            $view_mode = $mode_enabled[0];
        } else {
            $view_mode = 'simple';
        }

        $class_mode = 'ynultimatevideo_'. $view_mode .'-view';

        $this -> view -> mode_enabled = $mode_enabled;
        $this -> view -> class_mode = $class_mode;
        $this -> view -> view_mode = $view_mode;

        $page = $params['page'];
        $params['status'] = 1;
        $params['search'] = 1;
        if (!$page) $page = 1;
        $numberOfItems = $this->_getParam('numberOfItems', 10);
        $paginator = Engine_Api::_() -> getItemTable('ynultimatevideo_video') -> getVideosPaginator($params);
        $paginator -> setCurrentPageNumber($page);
        $paginator -> setItemCountPerPage($numberOfItems);
        $this -> view -> paginator = $paginator;
    }
}