<?php
    namespace Glowie\Core\View;

    use Glowie\Core\Http\Rails;
    use Glowie\Core\Traits\ElementTrait;
    use Glowie\Core\View\Buffer;
    use Glowie\Core\Exception\FileException;
    use Config;
    use Util;
    use BadMethodCallException;

    /**
     * View core for Glowie application.
     * @category View
     * @package glowieframework/glowie-core
     * @author Glowie
     * @copyright Copyright (c) Glowie
     * @license MIT
     * @link https://glowie.tk
     */
    class View{
        use ElementTrait;

        /**
         * View content.
         * @var string
         */
        private $_content;

        /**
         * View original filename.
         * @var string
         */
        private $_filename;

        /**
         * View helpers instance.
         * @var Helpers
         */
        private static $_helpers;

        /**
         * View local parameters.
         * @var array
         */
        private $_params;

        /**
         * Instantiates a new View.
         * @param string $view View filename to instantiate.
         * @param array $params (Optional) View parameters to parse.
         * @param bool $partial (Optional) Restrict view partial scope.
         */
        public function __construct(string $view, array $params = [], bool $partial = false){
            // Validate file
            $this->_filename = $view;
            $view = Util::location('views/' . $view . (!Util::endsWith($view, '.phtml') ? '.phtml' : ''));
            if(!is_file($view)) throw new FileException(sprintf('View file "%s" not found', $this->_filename));

            // Instantiate helpers
            $helpers = 'Glowie\Helpers\Helpers';
            if(!self::$_helpers && is_file(Util::location('views/helpers/Helpers.php'))) self::$_helpers = new $helpers;

            // Parse parameters
            $this->_params = $params;
            $globalParams = !$partial ? Rails::getController()->view->toArray() : [];
            $this->__constructTrait(array_merge($globalParams, $this->_params));

            // Render view
            if(Config::get('skeltch.enabled', true)) $view = Skeltch::run($view);
            $this->_content = $this->getBuffer($view);
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
                throw new BadMethodCallException('Helper method "' . $method .'()" is not defined (View: "' . $this->_filename . '")');
            }
        }

        /**
         * Gets a view buffer.
         * @param string $path View filename to include.
         * @return string The buffer contents as string.
         */
        private function getBuffer(string $path){
            Buffer::start();
            include($path);
            return Buffer::get();
        }

        /**
         * Renders a view file.
         * @param string $view View filename. Must be a **.phtml** file inside **app/views** folder, extension is not needed.
         * @param array $params (Optional) Parameters to pass into the view. Should be an associative array with each variable name and value.
         */
        public function renderView(string $view, array $params = []){
            Rails::getController()->renderView($view, array_merge($this->_params, $params));
        }

        /**
         * Renders a layout file.
         * @param string $layout Layout filename. Must be a **.phtml** file inside **app/views/layouts** folder, extension is not needed.
         * @param string|null $view (Optional) View filename to render within layout. You can place its content by using `$this->getView()`\
         * inside the layout file. Must be a **.phtml** file inside **app/views** folder, extension is not needed.
         * @param array $params (Optional) Parameters to pass into the rendered view and layout. Should be an associative array with each variable name and value.
         */
        public function renderLayout(string $layout, ?string $view = null, array $params = []){
            Rails::getController()->renderLayout($layout, $view, array_merge($this->_params, $params));
        }

        /**
         * Renders a view file in a private scope. No global or parent view properties will be inherited.
         * @param string $view View filename. Must be a **.phtml** file inside **app/views** folder, extension is not needed.
         * @param array $params (Optional) Parameters to pass into the view. Should be an associative array with each variable name and value.
         */
        public function renderPartial(string $view, array $params = []){
            Rails::getController()->renderPartial($view, $params);
        }

        /**
         * Returns the view content as string.
         * @return string View content.
         */
        public function getContent(){
            return $this->_content;
        }

    }

?>