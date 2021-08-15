<?php
    namespace Glowie\Core\View;

    use Glowie\Helpers\Helpers;
    use Glowie\Core\Http\Rails;
    use Glowie\Core\Element;
    use Glowie\Core\Config;
    use BadMethodCallException;

    /**
     * Layout core for Glowie application.
     * @category Layout
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) 2021
     * @license MIT
     * @link https://glowie.tk
     * @version 1.0
     */
    class Layout extends Element{

        /**
         * Layout view content.
         * @var string
         */
        private $_content;

        /**
         * View helpers instance.
         * @var Helpers
         */
        private static $_helpers;

        /**
         * Layout file path.
         * @var string
         */
        private $_path;

        /**
         * Instantiates a new Layout object.
         * @param string $layout Layout filename to instantiate.
         * @param string $view View filename to parse inside the layout.
         * @param array $params View parameters to parse.
         */
        public function __construct(string $layout, string $view, array $params){
            // Parse parameters
            $helpers = 'Glowie\Helpers\Helpers';
            if(!self::$_helpers) self::$_helpers = new $helpers;
            $this->_path = $layout;
            $viewData = Rails::getController()->view->toArray();
            if(!empty($viewData)) foreach ($viewData as $key => $value) $this->{$key} = $value;
            if(!empty($params)) foreach($params as $key => $value) $this->{$key} = $value;

            // Parse view
            if(!empty($view)){
                $view = new View($view, $params, false);
                $this->_content = $view->getContent();
            }

            // Render layout
            if(Config::get('skeltch', true)) $this->_path = Skeltch::run($this->_path);
            echo $this->getBuffer();
        }

        /**
         * Calls a helper method dynamically.
         * @param mixed $method Method to be called from `Glowie\Helpers\Helpers` class.
         * @param mixed $args Arguments to pass to the method.
         */
        public function __call($method, $args){
            if(is_callable([self::$_helpers, $method])){
                return call_user_func_array([self::$_helpers, $method], $args);
            }else{
                throw new BadMethodCallException('Layout: Method "' . $method .'" does not exist in "app/views/helpers/Helpers.php"');
            }
        }

        /**
         * Gets a layout buffer.
         * @return string The buffer contents as string.
         */
        private function getBuffer(){
            ob_start();
            include($this->_path);
            return ob_get_clean();
        }

        /**
         * Renders a view file.
         * @param string $view View filename. Must be a **.phtml** file inside **app/views** folder, extension is not needed.
         * @param array $params (Optional) Parameters to pass into the view. Should be an associative array with each variable name and value.
         * @return void
         */
        public function renderView(string $view, array $params = []){
            Rails::getController()->renderView($view, $params);
        }

        /**
         * Renders a layout file.
         * @param string $layout Layout filename. Must be a **.phtml** file inside **app/views/layouts** folder, extension is not needed.
         * @param string $view (Optional) View filename to render within layout. You can place its content by using `$this->getContent()`\
         * inside the layout file. Must be a **.phtml** file inside **app/views** folder, extension is not needed.
         * @param array $params (Optional) Parameters to pass into the rendered view and layout. Should be an associative array with each variable name and value.
         * @return void
         */
        public function renderLayout(string $layout, string $view = '', array $params = []){
            Rails::getController()->renderLayout($layout, $view, $params);
        }

        /**
         * Returns the layout view content as string.
         * @return string View content.
         */
        public function getContent(){
            return $this->_content;
        }

    }

?>