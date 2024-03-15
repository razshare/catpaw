package main

import "C"
import (
    "image/color"

    "gioui.org/app"
    "gioui.org/f32"
    "gioui.org/layout"
    "gioui.org/op"
    "gioui.org/op/clip"
    "gioui.org/op/paint"
    "gioui.org/text"
    "gioui.org/unit"
    "gioui.org/widget/material"
)

var ops op.Ops

type stringC = *C.char

func toString(value stringC) string {
    return C.GoString(value)
}

func toStringC(value string) stringC {
    return C.CString(value)
}

//export window
func window() int {
    window := app.NewWindow()
    return WindowRefs.Add(window).key
}

//export theme
func theme() int {
    return ThemeRefs.Add(material.NewTheme()).key
}

//export h1
func h1(theme int, labelC stringC) int {
    th := ThemeRefs.items[theme]
    label := material.H1(th, toString(labelC))
    return LabelRefs.Add(&label).key
}

//export h2
func h2(theme int, labelC stringC) int {
    th := ThemeRefs.items[theme]
    label := material.H2(th, toString(labelC))
    return LabelRefs.Add(&label).key
}

//export h3
func h3(theme int, labelC stringC) int {
    th := ThemeRefs.items[theme]
    label := material.H3(th, toString(labelC))
    return LabelRefs.Add(&label).key
}

//export h4
func h4(theme int, labelC stringC) int {
    th := ThemeRefs.items[theme]
    label := material.H4(th, toString(labelC))
    return LabelRefs.Add(&label).key
}

//export h5
func h5(theme int, labelC stringC) int {
    th := ThemeRefs.items[theme]
    label := material.H5(th, toString(labelC))
    return LabelRefs.Add(&label).key
}

//export h6
func h6(theme int, labelC stringC) int {
    th := ThemeRefs.items[theme]
    label := material.H6(th, toString(labelC))
    return LabelRefs.Add(&label).key
}

//export label
func label(theme int, sizeC float32, labelC stringC) int {
    th := ThemeRefs.items[theme]
    label := material.Label(th, unit.Sp(sizeC), toString(labelC))
    return LabelRefs.Add(&label).key
}

//export rgba
func rgba(r int16, g int16, b int16, a int16) int {
    c := color.NRGBA{R: uint8(r), G: uint8(g), B: uint8(b), A: uint8(a)}
    return NRGBARefs.Add(&c).key
}

//export labelLayout
func labelLayout(label int, context int) {
    ctx := ContextRefs.items[context]
    lbl := LabelRefs.items[label]
    lbl.Layout(*ctx)
}

//export context
func context(frameEvent int) int {
    fe := FrameEventRefs.items[frameEvent]
    ctx := app.NewContext(&ops, *fe)
    return ContextRefs.Add(&ctx).key
}

//export labelSetAlignment
func labelSetAlignment(label int, value uint8) {
    lbl := LabelRefs.items[label]
    lbl.Alignment = text.Alignment(value)
}

//export labelSetColor
func labelSetColor(label int, color int) {
    lbl := LabelRefs.items[label]
    clr := NRGBARefs.items[color]
    lbl.Color = *clr
}

//export pathStart
func pathStart(x float32, y float32) int {
    var p clip.Path
    p.Begin(&ops)
    p.Move(f32.Point{
        X: x,
        Y: y,
    })
    return PathRefs.Add(&p).key
}

//export lineTo
func lineTo(line int, x float32, y float32) {
    p := PathRefs.items[line]
    p.Line(f32.Point{
        X: x,
        Y: y,
    })
}

//export arcTo
func arcTo(line int, x1 float32, y1 float32, x2 float32, y2 float32, angle float32) {
    p := PathRefs.items[line]
    f1 := f32.Point{
        X: x1,
        Y: y1,
    }
    f2 := f32.Point{
        X: x2,
        Y: y2,
    }
    p.Arc(f1, f2, angle)
}

//export pathEnd
func pathEnd(line int, width float32, clr int) {
    p := PathRefs.items[line]
    c := NRGBARefs.items[clr]
    spec := p.End()

    paint.FillShape(&ops, *c,
        clip.Stroke{
            Path:  spec,
            Width: width,
        }.Op(),
    )
}

//export event
func event(window int) (int, int) {
    w := WindowRefs.items[window]
    event := w.NextEvent()
    switch e := event.(type) {
    case app.FrameEvent:
        return FrameEventRefs.Add(&e).key, 1
    case app.DestroyEvent:
        return DestroyEventRefs.Add(&e).key, 2
    }
    return -1, -1
}

//export reset
func reset() {
    ops.Reset()
}

//export draw
func draw(frameEvent int) {
    e := FrameEventRefs.items[frameEvent]
    e.Frame(&ops)
}

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

const RefWindow = 0
const RefFrameEvent = 1
const RefContext = 2
const RefLabel = 3
const RefRgba = 4
const RefTheme = 5

//export remove
func remove(refKey int, refType int) {
    switch refType {
    case RefWindow:
        WindowRefs.Remove(refKey)
    case RefFrameEvent:
        FrameEventRefs.Remove(refKey)
    case RefContext:
        ContextRefs.Remove(refKey)
    case RefLabel:
        LabelRefs.Remove(refKey)
    case RefRgba:
        NRGBARefs.Remove(refKey)
    case RefTheme:
        ThemeRefs.Remove(refKey)
    }
}

var WindowRefs = CreateReference[*app.Window]()
var FrameEventRefs = CreateReference[*app.FrameEvent]()
var DestroyEventRefs = CreateReference[*app.DestroyEvent]()
var ContextRefs = CreateReference[*layout.Context]()
var LabelRefs = CreateReference[*material.LabelStyle]()
var NRGBARefs = CreateReference[*color.NRGBA]()
var ThemeRefs = CreateReference[*material.Theme]()
var PathRefs = CreateReference[*clip.Path]()
var PathSpecRefs = CreateReference[*clip.PathSpec]()

func main() {
    //window := window()
    //theme := theme()
    //for {
    //  event, t := event(window)
    //
    //  if event >= 0 && t == 1 {
    //      reset()
    //      context := context(event)
    //      title := h1(theme, toStringC("Hello, Gio Ref"))
    //      maroon := rgba(127, 0, 0, 255)
    //      labelSetColor(title, maroon)
    //      labelSetAlignment(title, uint8(text.Middle))
    //      labelLayout(title, context)
    //      draw(event)
    //  }
    //}
}
