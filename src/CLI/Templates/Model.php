<?php
    namespace Glowie\Models;

    use Glowie\Core\Database\Model;

    /**
     * __FIREFLY_TEMPLATE_NAME__ model for Glowie application.
     * @category Model
     * @package glowieframework/glowie
     * @author Glowie
     * @copyright Copyright (c) Glowie
     * @license MIT
     * @link https://eugabrielsilva.tk/glowie
     */
    class __FIREFLY_TEMPLATE_NAME__ extends Model{

        /**
         * Model table name.
         * @var string
         */
        protected $_table = '__FIREFLY_TEMPLATE_TABLE__';

        /**
         * Model database connection name (from your app configuration).
         * @var string
         */
        protected $_database = 'default';

        /**
         * Table primary key name.
         * @var string
         */
        protected $_primaryKey = '__FIREFLY_TEMPLATE_PRIMARY__';

        /**
         * Table retrievable fields.
         * @var array
         */
        protected $_fields = [];

        /**
         * Table updatable fields.
         * @var array
         */
        protected $_updatable = [];

        /**
         * Initial model attributes.
         * @var array
         */
        protected $_attributes = [];

        /**
         * Table fields data types to cast.
         * @var array
         */
        protected $_casts = [];

        /**
         * Table fields data types to mutate.
         * @var array
         */
        protected $_mutators = [];

        /**
         * Handle timestamp fields.
         * @var bool
         */
        protected $_timestamps = false;

        /**
         * Use soft deletes in the table.
         * @var bool
         */
        protected $_softDeletes = false;

        /**
         * **Created at** field name (if timestamps enabled).
         * @var string
         */
        protected $_createdField = 'created_at';

        /**
         * **Updated at** field name (if timestamps enabled).
         * @var string
         */
        protected $_updatedField = 'updated_at';

        /**
         * **Deleted at** field name (if soft deletes enabled).
         * @var string
         */
        protected $_deletedField = 'deleted_at';

    }

?>