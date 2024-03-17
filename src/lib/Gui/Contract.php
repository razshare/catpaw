<?php
namespace CatPaw\Gui;

interface Operations {
}
interface GoFile {
}
interface GoImage {
}
interface Path {
}
interface Window {
}
interface Context {
}
interface FrameEvent {
}
interface Label {
}
interface Rgba {
}
interface Theme {
}

const LABEL_ALIGN_START  = 0;
const LABEL_ALIGN_END    = 1;
const LABEL_ALIGN_MIDDLE = 2;

const REF_WINDOW      = 0;
const REF_FRAME_EVENT = 1;
const REF_CONTEXT     = 2;
const REF_LABEL       = 3;
const REF_RGBA        = 4;
const REF_THEME       = 5;
const REF_GO_FILE     = 6;
const REF_GO_IMAGE    = 7;
const REF_OPERATIONS  = 8;

interface Contract {
    /**
     * Create a window.
     * @return Window
     */
    function window():Window;
    /**
     * Retrieve the next event.
     * @param  Window     $window
     * @return FrameEvent
     */
    function event(Window $window):FrameEvent;
    /**
     * Create a new material theme.
     * @return Theme
     */
    function theme():Theme;
    /**
     * Create a material H1 label.
     * @param  Theme  $theme Material theme.
     * @param  string $text  Text to render.
     * @return Label
     */
    function h1(Theme $theme, string $text):Label;
    /**
     * Create a material H2 label.
     * @param  Theme  $theme Material theme.
     * @param  string $text  Text to render.
     * @return Label
     */
    function h2(Theme $theme, string $text):Label;
    /**
     * Create a material H3 label.
     * @param  Theme  $theme Material theme.
     * @param  string $text  Text to render.
     * @return Label
     */
    function h3(Theme $theme, string $text):Label;
    /**
     * Create a material H4 label.
     * @param  Theme  $theme Material theme.
     * @param  string $text  Text to render.
     * @return Label
     */
    function h4(Theme $theme, string $text):Label;
    /**
     * Create a material H5 label.
     * @param  Theme  $theme Material theme.
     * @param  string $text  Text to render.
     * @return Label
     */
    function h5(Theme $theme, string $text):Label;
    /**
     * Create a material H6 label.
     * @param  Theme  $theme Material theme.
     * @param  string $text  Text to render.
     * @return Label
     */
    function h6(Theme $theme, string $text):Label;
    /**
     * Create
     * @param  Theme  $theme
     * @param  float  $size
     * @param  string $text
     * @return Label
     */
    function label(Theme $theme, float $size, string $text):Label;
    /**
     * Align a material label.
     * @param  Label $label label to modify.
     * @param  int   $align Alignment to apply.
     * @return void
     */
    function labelSetAlignment(Label $label, int $align):void;
    /**
     * Set the color (text) of a material label.
     * @param  Label $label Label to modify.
     * @param  Rgba  $color Material color to apply.
     * @return void
     */
    function labelSetColor(Label $label, Rgba $color):void;
    /**
     * Layout a label onto a context.
     * @param  Label   $label
     * @param  Context $context
     * @return void
     */
    function labelLayout(Label $label, Context $context):void;
    /**
     * Create a new context to draw on.
     * @param  FrameEvent $frameEvent
     * @return Context
     */
    function context(Operations $ops, FrameEvent $frameEvent):Context;
    /**
     * Create a material color.
     * @param  int  $r
     * @param  int  $g
     * @param  int  $b
     * @param  int  $a
     * @return Rgba
     */
    function rgba(int $r, int $g, int $b, int $a, ):Rgba;
    /**
     * Destroy an object reference from memory.
     * @param  Window|Context|FrameEvent|Label|Rgba|Theme $key
     * @param  int                                        $type
     * @return void
     */
    function destroy(mixed $key, int $type):void;
    /**
     * Reset operations.
     * @return void
     */
    function reset(Operations $ops):void;
    /**
     * Submit a frame to the Gpu.
     * @param  FrameEvent $frame Frame to draw.
     * @return void
     */
    function draw(Operations $ops, FrameEvent $frame):void;
    /**
     * Start a path at a position.
     * > **Note**\
     * > This doesn't draw anything to the canvas yet.\
     * > YOu can use other functions like `lineTo()` then `pathEnd()` to draw the line.
     * @param  float $x
     * @param  float $y
     * @return Path
     */
    function pathStart(Operations $ops, float $x, float $y):Path;
    /**
     * Add a line to path.
     * > **Note**\
     * > This doesn't draw anything to the canvas yet.\
     * > See `pathEnd()` for more info.
     * @param  float $x
     * @param  float $y
     * @param  Path  $path
     * @return void
     */
    function lineTo(Path $line, float $x, float $y):void;
    /**
     * Add an arc to path.
     * > **Note**\
     * > This doesn't draw anything to the canvas yet.\
     * > See `pathEnd()` for more info.
     * @param  Path  $line
     * @param  float $x1    X position of the _first_ focus point.
     * @param  float $y1    Y position of the _first_ focus point.
     * @param  float $x2    X position of the _second_ focus point.
     * @param  float $y2    Y position of the _second_ focus point.
     * @param  float $angle Angle of the arc.\
     *                      Value may range from 0 to 1.\
     *                      - Value of 0 is a line.
     *                      - Value of 1 (or above) is an arc that round-trips, which means it's a perfect circle.\
     *                      This also means that the end position of the path will be the same position the arc started from.
     * @return void
     */
    function arcTo(
        Path $line,
        float $x1,
        float $y1,
        float $x2,
        float $y2,
        float $angle,
    ):void;
    /**
     * Draw the path.
     * @param  float $width Width of the lines.
     * @param  Rgba  $color Color of the lines.
     * @param  Path  $path
     * @return void
     */
    function pathEnd(Operations $ops, Path $line, float $width, Rgba $color):void;
    /**
     * Open a file.
     *
     * > **Note**\
     * > This ia a Go file, you cannot manage this file from Php.
     * @param  string $fileName
     * @return GoFile
     */
    function openFile(string $fileName):GoFile;
    /**
     * Decode a file as an image.
     * @param  GoFile  $file
     * @return GoImage
     */
    function decodeImage(GoFile $file):GoImage;
    /**
     * Add image to the context.
     * @param  GoImage $image Image to add.
     * @return void
     */
    function addImage(
        Operations $ops,
        GoImage $image
    ):void;
    /**
     * Create operations.
     * @return Operations
     */
    function operations():Operations;
    function scale(Operations $ops, float $originX, float $originY, float $factorX, float $factorY):void;
    function rotate(Operations $ops, float $originX, float $originY, float $radians):void;
    function offset(Operations $ops, float $originX, float $originY):void;
    function shear(Operations $ops, float $originX, float $originY, float $radiansX, float $radiansY):void;
}
