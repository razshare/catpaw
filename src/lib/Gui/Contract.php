<?php
namespace CatPaw\Gui;

interface Window {
}
interface Context {
}
interface FrameEvent {
}
interface LabelStyle {
}
interface Rgba {
}
interface Theme {
}

const LABEL_ALIGN_START  = 0;
const LABEL_ALIGN_END    = 1;
const LABEL_ALIGN_MIDDLE = 2;

interface Contract {
    function appNewWindow():Window;
    function windowNextEvent(Window $window):FrameEvent;
    function materialNewTheme():Theme;
    function materialSetLabelAlignment(LabelStyle $label, int $align):void;
    function materialSetLabelColor(LabelStyle $label, Rgba $color):void;
    function materialH1(Theme $theme, string $text):LabelStyle;
    function materialH2(Theme $theme, string $text):LabelStyle;
    function materialH3(Theme $theme, string $text):LabelStyle;
    function materialH4(Theme $theme, string $text):LabelStyle;
    function materialH5(Theme $theme, string $text):LabelStyle;
    function materialH6(Theme $theme, string $text):LabelStyle;
    function materialLabel(Theme $theme, float $size, string $text):LabelStyle;
    function colorRgba(int $r, int $g, int $b, int $a, ):Rgba;
    function materialLabelStyleDrawToContext(LabelStyle $labelStyle, Context $context):void;
    function appNewContext(FrameEvent $frameEvent):Context;
    function submit(FrameEvent $frameEvent, Context $context):void;
    function remove(mixed $object):void;
}
