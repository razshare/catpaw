<?php
namespace CatPaw\Gui;

interface Window {
}
interface View {
}
interface Scene {
}
interface StatusBar {
}
interface KeyEvent {
}
interface WheelEvent {
}
interface ResizeEvent {
}
interface MouseEvent {
}
interface HoverEvent {
}
interface PixmapItem {
}
interface Text {
}
interface Image {
}
interface Button {
}
interface ProxyWidget {
}

const WindowCode      = 1;
const StatusBarCode   = 2;
const SceneCode       = 3;
const ViewCode        = 4;
const KeyEventCode    = 5;
const WheelEventCode  = 6;
const ResizeEventCode = 7;
const MouseEventCode  = 8;
const HoverEventCode  = 9;
const PixelMapCode    = 10;
const TextCode        = 11;
const ImageCode       = 12;
const PixmapCode      = 13;
const PushButtonCode  = 14;
const ProxyWidgetCode = 15;

interface Contract {
    // #################################
    // #################################
    // #################################
    // #################################
    // #################################
    // ======================[START]===> Application
    function application():void;
    function application_set_style(string $style):void;
    function application_execute():void;

    // #################################
    // #################################
    // #################################
    // #################################
    // #################################
    // ======================[START]===> Window
    function window():Window;
    function window_show(Window $window):void;
    function window_set_title(Window $window, string $title):void;
    function window_set_minimum_size(Window $window, int $width, int $height):void;
    function window_set_status_bar(Window $window, StatusBar $status_bar):void;
    function window_set_central_view(Window $window, View $view):void;

    // #################################
    // #################################
    // #################################
    // #################################
    // #################################
    // ======================[START]===> StatusBar
    function status_bar(Window $window):StatusBar;
    function status_bar_show_message(StatusBar $status_bar, string $message):void;

    // #################################
    // #################################
    // #################################
    // #################################
    // #################################
    // ======================[START]===> View
    function view():View;
    function view_set_scene(View $view, Scene $scene):void;
    function view_show(View $view):void;

    // #################################
    // #################################
    // #################################
    // #################################
    // #################################
    // ======================[START]===> Scene
    function scene():Scene;
    function scene_set_rect(Scene $scene, float $x, float $y, float $width, float $height):void;
    function scene_match_window(Scene $scene, Window $window):void;

    // #################################
    // #################################
    // #################################
    // #################################
    // #################################
    // ======================[START]===> Text
    function text(Scene $scene, string $text):Text;
    function text_set_default_color(Text $text, string $color):void;
    function text_set_position(Text $text, float $x, float $y):void;

    // #################################
    // #################################
    // #################################
    // #################################
    // #################################
    // ======================[START]===> Image
    function image(string $data, int $width, int $height):Image;
    function image_from_file_name(string $file_name, string $format):Image;
    function image_add_to_scene(Image $image, Scene $scene):PixmapItem;

    // #################################
    // #################################
    // #################################
    // #################################
    // #################################
    // ======================[START]===> PixmapItem
    function pixmap_item_set_position(PixmapItem $item, float $x, float $y):void;
    function pixmap_item_set_opacity(PixmapItem $item, float $opacity):void;
    function pixmap_item_set_scale(PixmapItem $item, float $scale):void;
    function pixmap_item_set_rotation(PixmapItem $item, float $angle):void;
    function pixmap_item_set_tooltip(PixmapItem $item, string $tooltip):void;
    function pixmap_item_set_visible(PixmapItem $item, bool $visible):void;
    function pixmap_item_set_z(PixmapItem $item, float $z):void;
    function pixmap_item_set_x(PixmapItem $item, float $x):void;
    function pixmap_item_set_y(PixmapItem $item, float $y):void;

    // #################################
    // #################################
    // #################################
    // #################################
    // #################################
    // ======================[START]===> Button
    function button(string $text):Button;
    function button_add_to_scene(Button $button, Scene $scene):ProxyWidget;

    // #################################
    // #################################
    // #################################
    // #################################
    // #################################
    // ======================[START]===> ProxyWidget
    function proxy_widget_set_position(ProxyWidget $proxy_widget, float $x, float $y):void;
    function proxy_widget_set_opacity(ProxyWidget $proxy_widget, float $opacity):void;
    function proxy_widget_set_tooltip(PixmapItem $item, string $tooltip):void;
    function proxy_widget_set_scale(PixmapItem $item, float $scale):void;
    function proxy_widget_set_rotation(PixmapItem $item, float $angle):void;
    function proxy_widget_set_enabled(ProxyWidget $proxy_widget, bool $enabled):void;
    function proxy_widget_set_visible(ProxyWidget $proxy_widget, bool $visible):void;
    function proxy_widget_set_x(ProxyWidget $proxy_widget, float $x):void;
    function proxy_widget_set_y(ProxyWidget $proxy_widget, float $y):void;
    function proxy_widget_set_z(ProxyWidget $proxy_widget, float $z):void;
}
