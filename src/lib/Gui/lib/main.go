//author: https://github.com/5k3105

package main

import (
    "C"
    "os"
    "strconv"

    "github.com/therecipe/qt/core"
    "github.com/therecipe/qt/gui"
    "github.com/therecipe/qt/widgets"
)

// Framework stuff

type Reference[T any] struct {
    items map[int]T
    index int
}

func (reference *Reference[T]) Add(item T) *ReferenceItem[T] {
    key := reference.index
    reference.index++
    reference.items[key] = item
    referenceItem := ReferenceItem[T]{
        reference: reference,
        key:       key,
    }
    return &referenceItem
}

func (reference *Reference[T]) Remove(key int) {
    delete(reference.items, key)
}

func CreateReference[T any]() *Reference[T] {
    return &Reference[T]{
        items: make(map[int]T),
        index: 0,
    }
}

type ReferenceItem[T any] struct {
    key       int
    reference *Reference[T]
}

func (item *ReferenceItem[T]) Get() *T {
    result := item.reference.items[item.key]
    return &result
}

type stringC = *C.char

func toString(value stringC) string {
    return C.GoString(value)
}

func toStringC(value string) stringC {
    return C.CString(value)
}

// Library stuff

var (
    Scene     *widgets.QGraphicsScene
    View      *widgets.QGraphicsView
    Item      *widgets.QGraphicsPixmapItem
    statusbar *widgets.QStatusBar
    mp        bool
)

func ItemMousePressEvent(event *widgets.QGraphicsSceneMouseEvent) {
    mp = true
    mousePosition := event.Pos()
    x, y := int(mousePosition.X()), int(mousePosition.Y())
    drawpixel(x, y)

}

func ItemMouseReleaseEvent(event *widgets.QGraphicsSceneMouseEvent) {
    mp = false

    Item.MousePressEventDefault(event) // absofukinlutely necessary for drag & draw !!

    //Item.MouseReleaseEventDefault(event) // worthless
}

func ItemMouseMoveEvent(event *widgets.QGraphicsSceneMouseEvent) {
    mousePosition := event.Pos()
    x, y := int(mousePosition.X()), int(mousePosition.Y())

    drawpixel(x, y)

}

func ItemHoverMoveEvent(event *widgets.QGraphicsSceneHoverEvent) {
    mousePosition := event.Pos()
    x, y := int(mousePosition.X()), int(mousePosition.Y())

    rgbValue := Item.Pixmap().ToImage().PixelColor2(x, y)
    r, g, b := rgbValue.Red(), rgbValue.Green(), rgbValue.Blue()
    statusbar.ShowMessage("x: "+strconv.Itoa(x)+" y: "+strconv.Itoa(y)+" r: "+strconv.Itoa(r)+" g: "+strconv.Itoa(g)+" b: "+strconv.Itoa(b), 0)

}

func drawpixel(x, y int) {

    if mp {
        img := Item.Pixmap().ToImage()
        img.SetPixelColor2(x, y, gui.NewQColor3(255, 255, 255, 255))
        Item.SetPixmap(gui.NewQPixmap().FromImage(img, 0))
    }

}

func keyPressEvent(e *gui.QKeyEvent) {

    switch int32(e.Key()) {
    case int32(core.Qt__Key_0):
        View.Scale(1.25, 1.25)

    case int32(core.Qt__Key_9):
        View.Scale(0.8, 0.8)
    }

}

func wheelEvent(e *widgets.QGraphicsSceneWheelEvent) {
    if gui.QGuiApplication_QueryKeyboardModifiers()&core.Qt__ShiftModifier != 0 {
        if e.Delta() > 0 {
            View.Scale(1.25, 1.25)
        } else {
            View.Scale(0.8, 0.8)
        }
    }
}

func resizeEvent(e *gui.QResizeEvent) {

    View.FitInView(Scene.ItemsBoundingRect(), core.Qt__KeepAspectRatio)

}

const WindowCode = 1
const StatusBarCode = 2
const SceneCode = 3
const ViewCode = 4
const KeyEventCode = 5
const WheelEventCode = 6
const ResizeEventCode = 7
const MouseEventCode = 8
const HoverEventCode = 9
const PixelMapCode = 10
const TextCode = 11
const ImageCode = 12
const PixmapCode = 13
const PushButtonCode = 14
const ProxyWidgetCode = 15

//export destroy
func destroy(refKey int, refType int) {
    switch refType {
    case WindowCode:
        WindowRefs.Remove(refKey)
    case StatusBarCode:
        StatusBarRefs.Remove(refKey)
    case SceneCode:
        SceneRefs.Remove(refKey)
    case ViewCode:
        ViewRefs.Remove(refKey)
    case KeyEventCode:
        KeyEventRefs.Remove(refKey)
    case WheelEventCode:
        WheelEventRefs.Remove(refKey)
    case ResizeEventCode:
        ResizeEventRefs.Remove(refKey)
    case MouseEventCode:
        MouseEventRefs.Remove(refKey)
    case HoverEventCode:
        HoverEventRefs.Remove(refKey)
    case PixelMapCode:
        PixmapItemRefs.Remove(refKey)
    case TextCode:
        TextRefs.Remove(refKey)
    case ImageCode:
        ImageRefs.Remove(refKey)
    }
}

var WindowRefs = CreateReference[*widgets.QMainWindow]()
var ViewRefs = CreateReference[*widgets.QGraphicsView]()
var SceneRefs = CreateReference[*widgets.QGraphicsScene]()
var StatusBarRefs = CreateReference[*widgets.QStatusBar]()
var KeyEventRefs = CreateReference[*gui.QKeyEvent]()
var WheelEventRefs = CreateReference[*widgets.QGraphicsSceneWheelEvent]()
var ResizeEventRefs = CreateReference[*gui.QResizeEvent]()
var MouseEventRefs = CreateReference[*widgets.QGraphicsSceneMouseEvent]()
var HoverEventRefs = CreateReference[*widgets.QGraphicsSceneHoverEvent]()
var PixmapItemRefs = CreateReference[*widgets.QGraphicsPixmapItem]()
var TextRefs = CreateReference[*widgets.QGraphicsTextItem]()
var ImageRefs = CreateReference[*gui.QImage]()
var PixmapItem = CreateReference[*widgets.QGraphicsPixmapItem]()
var PushButtonRefs = CreateReference[*widgets.QPushButton]()
var ProxyWidgetRefs = CreateReference[*widgets.QGraphicsProxyWidget]()

// #################################
// #################################
// #################################
// #################################
// #################################
// ======================[START]===> Application
//
//export application
func application() {
    widgets.NewQApplication(len(os.Args), os.Args)
}

//export application_set_style
func application_set_style(style stringC) {
    widgets.QApplication_SetStyle2(toString(style))
}

//export application_execute
func application_execute() {
    go func() {
        widgets.QApplication_Exec()
    }()
}

// #################################
// #################################
// #################################
// #################################
// #################################
// ======================[START]===> Window
//
//export window
func window() int {
    return WindowRefs.Add(widgets.NewQMainWindow(nil, 0)).key
}

//export window_show
func window_show(window int) {
    w := WindowRefs.items[window]
    w.Show()
}

//export window_set_title
func window_set_title(window int, title stringC) {
    w := WindowRefs.items[window]
    w.SetWindowTitle(toString(title))
}

//export window_set_minimum_size
func window_set_minimum_size(window int, width int, height int) {
    w := WindowRefs.items[window]
    w.SetMinimumSize2(width, height)
}

//export window_set_status_bar
func window_set_status_bar(window int, status_bar int) {
    w := WindowRefs.items[window]
    s := StatusBarRefs.items[status_bar]
    w.SetStatusBar(s)
}

//export window_set_central_view
func window_set_central_view(window int, view int) {
    w := WindowRefs.items[window]
    v := ViewRefs.items[view]
    w.SetCentralWidget(v)
}

// #################################
// #################################
// #################################
// #################################
// #################################
// ======================[START]===> StatusBar
//
//export status_bar
func status_bar(window int) int {
    w := WindowRefs.items[window]
    return StatusBarRefs.Add(widgets.NewQStatusBar(w)).key
}

//export status_bar_show_message
func status_bar_show_message(status_bar int, message stringC) {
    s := StatusBarRefs.items[status_bar]
    s.ShowMessage(toString(message), 0)
}

// #################################
// #################################
// #################################
// #################################
// #################################
// ======================[START]===> Scene
//
//export scene
func scene() int {
    return SceneRefs.Add(widgets.NewQGraphicsScene(nil)).key
}

//export scene_set_rect
func scene_set_rect(scene int, x float64, y float64, width float64, height float64) {
    s := SceneRefs.items[scene]
    s.SetSceneRect2(x, y, width, height)
}

//export scene_match_window
func scene_match_window(scene int, window int) {
    s := SceneRefs.items[scene]
    w := WindowRefs.items[window]
    r := w.Rect()
    s.SetSceneRect2(0, 0, float64(r.Width()), float64(r.Height()))
}

// #################################
// #################################
// #################################
// #################################
// #################################
// ======================[START]===> Text
//
//export text
func text(scene int, text stringC) int {
    s := SceneRefs.items[scene]
    t := s.AddText(toString(text), gui.NewQFont2("Helvetica", -1, -1, false))
    t.SetDefaultTextColor(gui.NewQColor6("black"))
    return TextRefs.Add(t).key
}

//export text_set_default_color
func text_set_default_color(text int, color stringC) {
    t := TextRefs.items[text]
    t.SetDefaultTextColor(gui.NewQColor6(toString(color)))
}

//export text_set_position
func text_set_position(text int, x float64, y float64) {
    t := TextRefs.items[text]
    t.SetPos2(x, y)
}

// #################################
// #################################
// #################################
// #################################
// #################################
// ======================[START]===> View
//
//export view
func view() int {
    return ViewRefs.Add(widgets.NewQGraphicsView(nil)).key
}

//export view_set_scene
func view_set_scene(view int, scene int) {
    v := ViewRefs.items[view]
    s := SceneRefs.items[scene]
    v.SetScene(s)

}

//export view_show
func view_show(view int) {
    v := ViewRefs.items[view]
    v.Show()
}

// #################################
// #################################
// #################################
// #################################
// #################################
// ======================[START]===> Image
//
//export image
func image(data stringC, width int, height int) int {
    img := gui.NewQImage4(toString(data), width, height, gui.QImage__Format_ARGB32)
    return ImageRefs.Add(img).key
}

//export image_from_file_name
func image_from_file_name(file_name stringC, format stringC) int {
    img := gui.NewQImage9(toString(file_name), toString(format))
    return ImageRefs.Add(img).key
}

//export image_add_to_scene
func image_add_to_scene(image int, scene int) int {
    img := ImageRefs.items[image]
    scn := SceneRefs.items[scene]
    pix := gui.NewQPixmap().FromImage(img, 0)
    item := scn.AddPixmap(pix)
    scn.AddItem(item)
    return PixmapItemRefs.Add(item).key
}

// #################################
// #################################
// #################################
// #################################
// #################################
// ======================[START]===> PixmapItem
//
//export pixmap_item_set_position
func pixmap_item_set_position(pixmap_item int, x float64, y float64) {
    p := PixmapItemRefs.items[pixmap_item]
    p.SetPos2(x, y)
}

//export pixmap_item_set_opacity
func pixmap_item_set_opacity(pixmap_item int, opacity float64) {
    p := PixmapItemRefs.items[pixmap_item]
    p.SetOpacity(opacity)
}

//export pixmap_item_set_scale
func pixmap_item_set_scale(pixmap_item int, scale float64) {
    p := PixmapItemRefs.items[pixmap_item]
    p.SetScale(scale)
}

//export pixmap_item_set_rotation
func pixmap_item_set_rotation(pixmap_item int, angle float64) {
    p := PixmapItemRefs.items[pixmap_item]
    p.SetRotation(angle)
}

//export pixmap_item_set_tooltip
func pixmap_item_set_tooltip(pixmap_item int, tooltip stringC) {
    p := PixmapItemRefs.items[pixmap_item]
    p.SetToolTip(toString(tooltip))
}

//export pixmap_item_set_visible
func pixmap_item_set_visible(pixmap_item int, visible bool) {
    p := PixmapItemRefs.items[pixmap_item]
    p.SetVisible(visible)
}

//export pixmap_item_set_z
func pixmap_item_set_z(pixmap_item int, z float64) {
    p := PixmapItemRefs.items[pixmap_item]
    p.SetZValue(z)
}

//export pixmap_item_set_x
func pixmap_item_set_x(pixmap_item int, x float64) {
    p := PixmapItemRefs.items[pixmap_item]
    p.SetX(x)
}

//export pixmap_item_set_y
func pixmap_item_set_y(pixmap_item int, y float64) {
    p := PixmapItemRefs.items[pixmap_item]
    p.SetX(y)
}

// #################################
// #################################
// #################################
// #################################
// #################################
// ======================[START]===> Button
//
//export button
func button(text stringC) int {
    btn := widgets.NewQPushButton2(toString(text), nil)
    return PushButtonRefs.Add(btn).key
}

//export button_add_to_scene
func button_add_to_scene(button int, scene int) int {
    b := PushButtonRefs.items[button]
    s := SceneRefs.items[scene]
    item := s.AddWidget(b, core.Qt__Widget)
    return ProxyWidgetRefs.Add(item).key
}

// #################################
// #################################
// #################################
// #################################
// #################################
// ======================[START]===> ProxyWidget
//
//export proxy_widget_set_position
func proxy_widget_set_position(proxy_widget int, x float64, y float64) {
    p := ProxyWidgetRefs.items[proxy_widget]
    p.SetPos2(x, y)
}

//export proxy_widget_set_enabled
func proxy_widget_set_enabled(proxy_widget int, enabled bool) {
    p := ProxyWidgetRefs.items[proxy_widget]
    p.SetEnabled(enabled)
}

//export proxy_widget_set_visible
func proxy_widget_set_visible(proxy_widget int, visible bool) {
    p := ProxyWidgetRefs.items[proxy_widget]
    p.SetVisible(visible)
}

//export proxy_widget_set_opacity
func proxy_widget_set_opacity(proxy_widget int, opacity float64) {
    p := ProxyWidgetRefs.items[proxy_widget]
    p.SetOpacity(opacity)
}

//export proxy_widget_set_x
func proxy_widget_set_x(proxy_widget int, x float64) {
    p := ProxyWidgetRefs.items[proxy_widget]
    p.SetX(x)
}

//export proxy_widget_set_y
func proxy_widget_set_y(proxy_widget int, y float64) {
    p := ProxyWidgetRefs.items[proxy_widget]
    p.SetY(y)
}

//export proxy_widget_set_z
func proxy_widget_set_z(proxy_widget int, z float64) {
    p := ProxyWidgetRefs.items[proxy_widget]
    p.SetZValue(z)
}

//export proxy_widget_set_tooltip
func proxy_widget_set_tooltip(proxy_widget int, tooltip stringC) {
    p := ProxyWidgetRefs.items[proxy_widget]
    p.SetToolTip(toString(tooltip))
}

//export proxy_widget_set_scale
func proxy_widget_set_scale(proxy_widget int, scale float64) {
    p := ProxyWidgetRefs.items[proxy_widget]
    p.SetScale(scale)
}

//export proxy_widget_set_rotation
func proxy_widget_set_rotation(proxy_widget int, angle float64) {
    p := ProxyWidgetRefs.items[proxy_widget]
    p.SetRotation(angle)
}

func main() {
    // gui.NewQIm
    // widgets.NewQApplication(len(os.Args), os.Args)

    // // Main Window
    // var window = widgets.NewQMainWindow(nil, 0)
    // window.SetWindowTitle("Sprite Editor")
    // window.SetMinimumSize2(360, 520)

    // // Statusbar
    // statusbar = widgets.NewQStatusBar(window)
    // window.SetStatusBar(statusbar)

    // Scene = widgets.NewQGraphicsScene(nil)
    // View = widgets.NewQGraphicsView(nil)

    // Scene.ConnectKeyPressEvent(keyPressEvent)
    // Scene.ConnectWheelEvent(wheelEvent)
    // View.ConnectResizeEvent(resizeEvent)

    // dx, dy := 16, 32

    // img := gui.NewQImage3(dx, dy, gui.QImage__Format_ARGB32)

    // for i := 0; i < dx; i++ {
    //  for j := 0; j < dy; j++ {
    //      img.SetPixelColor2(i, j, gui.NewQColor3(i*2, j*8, i*2, 255))

    //  }
    // }

    // //img = img.Scaled2(dx*2,dy,core.Qt__IgnoreAspectRatio, core.Qt__FastTransformation)

    // Item = widgets.NewQGraphicsPixmapItem2(gui.NewQPixmap().FromImage(img, 0), nil)

    // Item.ConnectMouseMoveEvent(ItemMouseMoveEvent)
    // Item.ConnectMousePressEvent(ItemMousePressEvent)
    // Item.ConnectMouseReleaseEvent(ItemMouseReleaseEvent)

    // Item.SetAcceptHoverEvents(true)
    // Item.ConnectHoverMoveEvent(ItemHoverMoveEvent)

    // Scene.AddItem(Item)

    // View.SetScene(Scene)
    // View.Show()

    // statusbar.ShowMessage(core.QCoreApplication_ApplicationDirPath(), 0)

    // // Set Central Widget
    // window.SetCentralWidget(View)

    // // Run App
    // widgets.QApplication_SetStyle2("fusion")
    // window.Show()
    // widgets.QApplication_Exec()
}
