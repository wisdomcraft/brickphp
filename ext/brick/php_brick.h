/* brick extension for PHP */

#ifndef PHP_BRICK_H
# define PHP_BRICK_H

extern zend_module_entry brick_module_entry;
# define phpext_brick_ptr &brick_module_entry

# define PHP_BRICK_VERSION "0.1.0"

# if defined(ZTS) && defined(COMPILE_DL_BRICK)
ZEND_TSRMLS_CACHE_EXTERN()
# endif

#endif	/* PHP_BRICK_H */

PHP_FUNCTION(brick_version);
