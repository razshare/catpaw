//author: https://github.com/5k3105

package main

import (
    "C"
    "os"

    "github.com/therecipe/qt/core"
    "github.com/therecipe/qt/gui"
    "github.com/therecipe/qt/widgets"
)

// Framework stuff

type stringC = *C.char

func toString(value stringC) string {
    return C.GoString(value)
}

func toStringC(value string) stringC {
    return C.CString(value)
}

type Global struct {
    items map[uint64]*any
    index uint64
}

var global = &Global{
    items: make(map[uint64]*any),
    index: 0,
}

func ref[T any](item *T) uint64 {
    key := global.index
    global.index++
    payload := any(*item)
    global.items[key] = &payload
    return key
}

func unref[T any](key uint64) *T {
    item := global.items[key]
    result := (*item).(T)
    return &result
}

func remove(key uint64) {
    delete(global.items, key)
}

// Library stuff

//export destroy
func destroy(key uint64) {
    remove(key)
}

// var WindowRefs = CreateReference[*widgets.QMainWindow]()
// var ViewRefs = CreateReference[*widgets.QGraphicsView]()
// var SceneRefs = CreateReference[*widgets.QGraphicsScene]()
// var StatusBarRefs = CreateReference[*widgets.QStatusBar]()
// var KeyEventRefs = CreateReference[*gui.QKeyEvent]()
// var WheelEventRefs = CreateReference[*widgets.QGraphicsSceneWheelEvent]()
// var ResizeEventRefs = CreateReference[*gui.QResizeEvent]()
// var MouseEventRefs = CreateReference[*widgets.QGraphicsSceneMouseEvent]()
// var HoverEventRefs = CreateReference[*widgets.QGraphicsSceneHoverEvent]()
// var PixmapItemRefs = CreateReference[*widgets.QGraphicsPixmapItem]()
// var TextRefs = CreateReference[*widgets.QGraphicsTextItem]()
// var ImageRefs = CreateReference[*gui.QImage]()
// var PixmapItem = CreateReference[*widgets.QGraphicsPixmapItem]()
// var PushButtonRefs = CreateReference[*widgets.QPushButton]()
// var ProxyWidgetRefs = CreateReference[*widgets.QGraphicsProxyWidget]()

// var (
//  Scene     *widgets.QGraphicsScene
//  View      *widgets.QGraphicsView
//  Item      *widgets.QGraphicsPixmapItem
//  statusbar *widgets.QStatusBar
//  mp        bool
// )

// func ItemMousePressEvent(event *widgets.QGraphicsSceneMouseEvent) {
//  mp = true
//  mousePosition := event.Pos()
//  x, y := int(mousePosition.X()), int(mousePosition.Y())
//  drawpixel(x, y)

// }

// func ItemMouseReleaseEvent(event *widgets.QGraphicsSceneMouseEvent) {
//  mp = false

//  Item.MousePressEventDefault(event) // absofukinlutely necessary for drag & draw !!

//  //Item.MouseReleaseEventDefault(event) // worthless
// }

// func ItemMouseMoveEvent(event *widgets.QGraphicsSceneMouseEvent) {
//  mousePosition := event.Pos()
//  x, y := int(mousePosition.X()), int(mousePosition.Y())

//  drawpixel(x, y)

// }

// func ItemHoverMoveEvent(event *widgets.QGraphicsSceneHoverEvent) {
//  mousePosition := event.Pos()
//  x, y := int(mousePosition.X()), int(mousePosition.Y())

//  rgbValue := Item.Pixmap().ToImage().PixelColor2(x, y)
//  r, g, b := rgbValue.Red(), rgbValue.Green(), rgbValue.Blue()
//  statusbar.ShowMessage("x: "+strconv.Itoa(x)+" y: "+strconv.Itoa(y)+" r: "+strconv.Itoa(r)+" g: "+strconv.Itoa(g)+" b: "+strconv.Itoa(b), 0)

// }

// func drawpixel(x, y int) {

//  if mp {
//      img := Item.Pixmap().ToImage()
//      img.SetPixelColor2(x, y, gui.NewQColor3(255, 255, 255, 255))
//      Item.SetPixmap(gui.NewQPixmap().FromImage(img, 0))
//  }

// }

// func keyPressEvent(e *gui.QKeyEvent) {

//  switch int32(e.Key()) {
//  case int32(core.Qt__Key_0):
//      View.Scale(1.25, 1.25)

//  case int32(core.Qt__Key_9):
//      View.Scale(0.8, 0.8)
//  }

// }

// func wheelEvent(e *widgets.QGraphicsSceneWheelEvent) {
//  if gui.QGuiApplication_QueryKeyboardModifiers()&core.Qt__ShiftModifier != 0 {
//      if e.Delta() > 0 {
//          View.Scale(1.25, 1.25)
//      } else {
//          View.Scale(0.8, 0.8)
//      }
//  }
// }

// func resizeEvent(e *gui.QResizeEvent) {

//  View.FitInView(Scene.ItemsBoundingRect(), core.Qt__KeepAspectRatio)

// }

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
func window() uint64 {
    return ref(widgets.NewQMainWindow(nil, 0))
}

//export window_show
func window_show(window uint64) {
    w := unref[widgets.QMainWindow](window)
    w.Show()
}

//export window_set_title
func window_set_title(window uint64, title stringC) {
    w := unref[widgets.QMainWindow](window)
    w.SetWindowTitle(toString(title))
}

//export window_set_minimum_size
func window_set_minimum_size(window uint64, width int, height int) {
    w := unref[widgets.QMainWindow](window)
    w.SetMinimumSize2(width, height)
}

//export window_set_status_bar
func window_set_status_bar(window uint64, status_bar uint64) {
    w := unref[widgets.QMainWindow](window)
    s := unref[widgets.QStatusBar](status_bar)
    w.SetStatusBar(s)
}

//export window_set_central_view
func window_set_central_view(window uint64, view uint64) {
    w := unref[widgets.QMainWindow](window)
    v := unref[widgets.QGraphicsView](view)
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
func status_bar(window uint64) uint64 {
    w := unref[widgets.QMainWindow](window)
    return ref(widgets.NewQStatusBar(w))
}

//export status_bar_show_message
func status_bar_show_message(status_bar uint64, message stringC) {
    s := unref[widgets.QStatusBar](status_bar)
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
func scene() uint64 {
    return ref(widgets.NewQGraphicsScene(nil))
}

//export scene_set_rect
func scene_set_rect(scene uint64, x float64, y float64, width float64, height float64) {
    s := unref[widgets.QGraphicsScene](scene)
    s.SetSceneRect2(x, y, width, height)
}

//export scene_match_window
func scene_match_window(scene uint64, window uint64) {
    s := unref[widgets.QGraphicsScene](scene)
    w := unref[widgets.QMainWindow](window)
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
func text(scene uint64, text stringC) uint64 {
    s := unref[widgets.QGraphicsScene](scene)
    t := s.AddText(toString(text), gui.NewQFont2("Helvetica", -1, -1, false))
    t.SetDefaultTextColor(gui.NewQColor6("black"))
    return ref(t)
}

//export text_set_default_color
func text_set_default_color(text uint64, color stringC) {
    t := unref[widgets.QGraphicsTextItem](text)
    t.SetDefaultTextColor(gui.NewQColor6(toString(color)))
}

//export text_set_position
func text_set_position(text uint64, x float64, y float64) {
    t := unref[widgets.QGraphicsTextItem](text)
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
func view() uint64 {
    return ref(widgets.NewQGraphicsView(nil))
}

//export view_set_scene
func view_set_scene(view uint64, scene uint64) {
    v := unref[widgets.QGraphicsView](view)
    s := unref[widgets.QGraphicsScene](scene)
    v.SetScene(s)

}

//export view_show
func view_show(view uint64) {
    v := unref[widgets.QGraphicsView](view)
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
func image(data stringC, width int, height int) uint64 {
    img := gui.NewQImage4(toString(data), width, height, gui.QImage__Format_ARGB32)
    return ref(img)
}

//export image_from_file_name
func image_from_file_name(file_name stringC, format stringC) uint64 {
    img := gui.NewQImage9(toString(file_name), toString(format))
    return ref(img)
}

//export image_add_to_scene
func image_add_to_scene(image uint64, scene uint64) uint64 {
    img := unref[gui.QImage](image)
    scn := unref[widgets.QGraphicsScene](scene)
    pix := gui.NewQPixmap().FromImage(img, 0)
    item := scn.AddPixmap(pix)
    scn.AddItem(item)
    return ref(item)
}

// #################################
// #################################
// #################################
// #################################
// #################################
// ======================[START]===> PixmapItem
//
//export pixmap_item_set_position
func pixmap_item_set_position(pixmap_item uint64, x float64, y float64) {
    p := unref[widgets.QGraphicsPixmapItem](pixmap_item)
    p.SetPos2(x, y)
}

//export pixmap_item_set_opacity
func pixmap_item_set_opacity(pixmap_item uint64, opacity float64) {
    p := unref[widgets.QGraphicsPixmapItem](pixmap_item)
    p.SetOpacity(opacity)
}

//export pixmap_item_set_scale
func pixmap_item_set_scale(pixmap_item uint64, scale float64) {
    p := unref[widgets.QGraphicsPixmapItem](pixmap_item)
    p.SetScale(scale)
}

//export pixmap_item_set_rotation
func pixmap_item_set_rotation(pixmap_item uint64, angle float64) {
    p := unref[widgets.QGraphicsPixmapItem](pixmap_item)
    p.SetRotation(angle)
}

//export pixmap_item_set_tooltip
func pixmap_item_set_tooltip(pixmap_item uint64, tooltip stringC) {
    p := unref[widgets.QGraphicsPixmapItem](pixmap_item)
    p.SetToolTip(toString(tooltip))
}

//export pixmap_item_set_visible
func pixmap_item_set_visible(pixmap_item uint64, visible bool) {
    p := unref[widgets.QGraphicsPixmapItem](pixmap_item)
    p.SetVisible(visible)
}

//export pixmap_item_set_z
func pixmap_item_set_z(pixmap_item uint64, z float64) {
    p := unref[widgets.QGraphicsPixmapItem](pixmap_item)
    p.SetZValue(z)
}

//export pixmap_item_set_x
func pixmap_item_set_x(pixmap_item uint64, x float64) {
    p := unref[widgets.QGraphicsPixmapItem](pixmap_item)
    p.SetX(x)
}

//export pixmap_item_set_y
func pixmap_item_set_y(pixmap_item uint64, y float64) {
    p := unref[widgets.QGraphicsPixmapItem](pixmap_item)
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
func button(text stringC) uint64 {
    btn := widgets.NewQPushButton2(toString(text), nil)
    return ref(btn)
}

//export button_add_to_scene
func button_add_to_scene(button uint64, scene uint64) uint64 {
    b := unref[widgets.QPushButton](button)
    s := unref[widgets.QGraphicsScene](scene)
    item := s.AddWidget(b, core.Qt__Widget)
    return ref(item)
}

// #################################
// #################################
// #################################
// #################################
// #################################
// ======================[START]===> ProxyWidget
//
//export proxy_widget_set_position
func proxy_widget_set_position(proxy_widget uint64, x float64, y float64) {
    p := unref[widgets.QGraphicsProxyWidget](proxy_widget)
    p.SetPos2(x, y)
}

//export proxy_widget_set_enabled
func proxy_widget_set_enabled(proxy_widget uint64, enabled bool) {
    p := unref[widgets.QGraphicsProxyWidget](proxy_widget)
    p.SetEnabled(enabled)
}

//export proxy_widget_set_visible
func proxy_widget_set_visible(proxy_widget uint64, visible bool) {
    p := unref[widgets.QGraphicsProxyWidget](proxy_widget)
    p.SetVisible(visible)
}

//export proxy_widget_set_opacity
func proxy_widget_set_opacity(proxy_widget uint64, opacity float64) {
    p := unref[widgets.QGraphicsProxyWidget](proxy_widget)
    p.SetOpacity(opacity)
}

//export proxy_widget_set_x
func proxy_widget_set_x(proxy_widget uint64, x float64) {
    p := unref[widgets.QGraphicsProxyWidget](proxy_widget)
    p.SetX(x)
}

//export proxy_widget_set_y
func proxy_widget_set_y(proxy_widget uint64, y float64) {
    p := unref[widgets.QGraphicsProxyWidget](proxy_widget)
    p.SetY(y)
}

//export proxy_widget_set_z
func proxy_widget_set_z(proxy_widget uint64, z float64) {
    p := unref[widgets.QGraphicsProxyWidget](proxy_widget)
    p.SetZValue(z)
}

//export proxy_widget_set_tooltip
func proxy_widget_set_tooltip(proxy_widget uint64, tooltip stringC) {
    p := unref[widgets.QGraphicsProxyWidget](proxy_widget)
    p.SetToolTip(toString(tooltip))
}

//export proxy_widget_set_scale
func proxy_widget_set_scale(proxy_widget uint64, scale float64) {
    p := unref[widgets.QGraphicsProxyWidget](proxy_widget)
    p.SetScale(scale)
}

//export proxy_widget_set_rotation
func proxy_widget_set_rotation(proxy_widget uint64, angle float64) {
    p := unref[widgets.QGraphicsProxyWidget](proxy_widget)
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
