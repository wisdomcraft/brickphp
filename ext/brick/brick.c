/* brick extension for PHP */

#ifdef HAVE_CONFIG_H
# include "config.h"
#endif

#include "php.h"
#include "ext/standard/info.h"
#include "php_brick.h"

/* For compatibility with older PHP versions */
#ifndef ZEND_PARSE_PARAMETERS_NONE
#define ZEND_PARSE_PARAMETERS_NONE() \
    ZEND_PARSE_PARAMETERS_START(0, 0) \
    ZEND_PARSE_PARAMETERS_END()
#endif

/* {{{ void brick_test1()
 */
PHP_FUNCTION(brick_test1)
{
    ZEND_PARSE_PARAMETERS_NONE();

    php_printf("The extension %s is loaded and working!\r\n", "brick");
}
/* }}} */

/* {{{ string brick_test2( [ string $var ] )
 */
PHP_FUNCTION(brick_test2)
{
    char *var = "World";
    size_t var_len = sizeof("World") - 1;
    zend_string *retval;

    ZEND_PARSE_PARAMETERS_START(0, 1)
        Z_PARAM_OPTIONAL
        Z_PARAM_STRING(var, var_len)
    ZEND_PARSE_PARAMETERS_END();

    retval = strpprintf(0, "Hello %s", var);

    RETURN_STR(retval);
}
/* }}}*/

/* {{{ PHP_RINIT_FUNCTION
 */
PHP_RINIT_FUNCTION(brick)
{
#if defined(ZTS) && defined(COMPILE_DL_BRICK)
    ZEND_TSRMLS_CACHE_UPDATE();
#endif

    return SUCCESS;
}
/* }}} */

/* {{{ PHP_MINFO_FUNCTION
 */
PHP_MINFO_FUNCTION(brick)
{
    php_info_print_table_start();
    php_info_print_table_header(2, "brick support", "enabled");
    php_info_print_table_end();
}
/* }}} */

/* {{{ arginfo
 */
ZEND_BEGIN_ARG_INFO(arginfo_brick_test1, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO(arginfo_brick_test2, 0)
    ZEND_ARG_INFO(0, str)
ZEND_END_ARG_INFO()
/* }}} */

/* {{{ brick_functions[]
 */
static const zend_function_entry brick_functions[] = {
    PHP_FE(brick_test1,     arginfo_brick_test1)
    PHP_FE(brick_test2,     arginfo_brick_test2)
    PHP_FE(brick_version,   NULL)
    PHP_FE_END
};
/* }}} */

/* {{{ brick_module_entry
 */
zend_module_entry brick_module_entry = {
    STANDARD_MODULE_HEADER,
    "brick",                    /* Extension name */
    brick_functions,            /* zend_function_entry */
    NULL,                            /* PHP_MINIT - Module initialization */
    NULL,                            /* PHP_MSHUTDOWN - Module shutdown */
    PHP_RINIT(brick),            /* PHP_RINIT - Request initialization */
    NULL,                            /* PHP_RSHUTDOWN - Request shutdown */
    PHP_MINFO(brick),            /* PHP_MINFO - Module info */
    PHP_BRICK_VERSION,        /* Version */
    STANDARD_MODULE_PROPERTIES
};
/* }}} */

#ifdef COMPILE_DL_BRICK
# ifdef ZTS
ZEND_TSRMLS_CACHE_DEFINE()
# endif
ZEND_GET_MODULE(brick)
#endif


/* {{{ string brick_version()
 */
PHP_FUNCTION(brick_version){
    zend_string *retval;
    retval = strpprintf(0, "%s", "0.1");
    RETURN_STR(retval);
}
/* }}} */
