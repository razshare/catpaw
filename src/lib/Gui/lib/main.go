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

const ApplicationCode = 0
const WindowCode = 1
const StatusBarCode = 2
const SceneCode = 3
const ViewCode = 4
const KeyEventCode = 5
const WheelEventCode = 6
const ResizeEventCode = 7

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
var PixelMapRefs = CreateReference[*widgets.QGraphicsPixmapItem]()
var TextRefs = CreateReference[*widgets.QGraphicsTextItem]()

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

func main() {
    widgets.NewQApplication(len(os.Args), os.Args)

    // Main Window
    var window = widgets.NewQMainWindow(nil, 0)
    window.SetWindowTitle("Sprite Editor")
    window.SetMinimumSize2(360, 520)

    // Statusbar
    statusbar = widgets.NewQStatusBar(window)
    window.SetStatusBar(statusbar)

    Scene = widgets.NewQGraphicsScene(nil)
    View = widgets.NewQGraphicsView(nil)

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

    View.SetScene(Scene)
    View.Show()

    statusbar.ShowMessage(core.QCoreApplication_ApplicationDirPath(), 0)

    // Set Central Widget
    window.SetCentralWidget(View)

    // Run App
    widgets.QApplication_SetStyle2("fusion")
    window.Show()
    widgets.QApplication_Exec()
}
