<?php
namespace CatPaw\Web\Interfaces;

/**
 * 
 * @package CatPaw\Web\Interfaces
 */
interface RenderInterface {
    /**
     * Start rendering a document by redirecting the output buffer into a response modifier.
     * # Warning
     * __Do not__ execute async code after invoking this method!
     * 
     * # Example
     * ```php
     * <?php return static function(RenderInterface $render) { ?>
     *     <?php $render->start() ?>
     *     <!DOCTYPE html>
     *     <html lang="en">
     *         <head>
     *             <meta charset="UTF-8">
     *             <meta name="viewport" content="width=device-width, initial-scale=1.0">
     *             <title>Document</title>
     *         </head>
     *         <body>
     *             <span>Hello world</span>
     *         </body>
     *     </html>
     * <?php } ?>
     * 
     * @return self
     */
    public function start():self;

    /**
     * Cleans the output buffer.\
     * You can safely resume invoking async code after invoking this method.
     * @return self
     */
    public function stop():self;

    /**
     * Get the response modifier.
     * @return ResponseModifierInterface
     */
    public function response():ResponseModifierInterface;
}