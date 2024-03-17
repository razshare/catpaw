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
interface PixelMap {
}
interface Text {
}

const ApplicationCode = 0;
const WindowCode      = 1;
const StatusBarCode   = 2;
const SceneCode       = 3;
const ViewCode        = 4;
const KeyEventCode    = 5;
const WheelEventCode  = 6;
const ResizeEventCode = 7;

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


    // #################################
    // #################################
    // #################################
    // #################################
    // #################################
    // ======================[START]===> Text
    function text(Scene $scene, string $text):Text;
    function text_set_default_color(Text $text, string $color):void;
    function text_set_position(Text $text, float $x, float $y):void;
}
