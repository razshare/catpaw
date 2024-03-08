package main

import "C"
import (
    "gioui.org/layout"
    "gioui.org/unit"
    "image/color"

    "gioui.org/app"
    "gioui.org/op"
    "gioui.org/text"
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

//export appNewWindow
func appNewWindow() int {
    window := app.NewWindow()
    return WindowRefs.Add(window).key
}

//export materialNewTheme
func materialNewTheme() int {
    return ThemeRefs.Add(material.NewTheme()).key
}

//export materialH1
func materialH1(theme int, labelC stringC) int {
    th := ThemeRefs.items[theme]
    label := material.H1(th, toString(labelC))
    return LabelStyleRefs.Add(&label).key
}

//export materialH2
func materialH2(theme int, labelC stringC) int {
    th := ThemeRefs.items[theme]
    label := material.H2(th, toString(labelC))
    return LabelStyleRefs.Add(&label).key
}

//export materialH3
func materialH3(theme int, labelC stringC) int {
    th := ThemeRefs.items[theme]
    label := material.H3(th, toString(labelC))
    return LabelStyleRefs.Add(&label).key
}

//export materialH4
func materialH4(theme int, labelC stringC) int {
    th := ThemeRefs.items[theme]
    label := material.H4(th, toString(labelC))
    return LabelStyleRefs.Add(&label).key
}

//export materialH5
func materialH5(theme int, labelC stringC) int {
    th := ThemeRefs.items[theme]
    label := material.H5(th, toString(labelC))
    return LabelStyleRefs.Add(&label).key
}

//export materialH6
func materialH6(theme int, labelC stringC) int {
    th := ThemeRefs.items[theme]
    label := material.H6(th, toString(labelC))
    return LabelStyleRefs.Add(&label).key
}

//export materialLabel
func materialLabel(theme int, sizeC float32, labelC stringC) int {
    th := ThemeRefs.items[theme]
    label := material.Label(th, unit.Sp(sizeC), toString(labelC))
    return LabelStyleRefs.Add(&label).key
}

//export colorRgba
func colorRgba(r int16, g int16, b int16, a int16) int {
    c := color.NRGBA{R: uint8(r), G: uint8(g), B: uint8(b), A: uint8(a)}
    return NRGBARefs.Add(&c).key
}

//export materialLabelStyleDrawToContext
func materialLabelStyleDrawToContext(label int, context int) {
    ctx := ContextRefs.items[context]
    lbl := LabelStyleRefs.items[label]
    lbl.Layout(*ctx)
}

//export appNewContext
func appNewContext(frameEvent int) int {
    fe := FrameEventRefs.items[frameEvent]
    ctx := app.NewContext(&ops, *fe)
    return ContextRefs.Add(&ctx).key
}

//export submit
func submit(frameEvent int, context int) {
    fe := FrameEventRefs.items[frameEvent]
    ctx := ContextRefs.items[context]
    fe.Frame(ctx.Ops)
}

//export materialSetLabelAlignment
func materialSetLabelAlignment(label int, value uint8) {
    lbl := LabelStyleRefs.items[label]
    lbl.Alignment = text.Alignment(value)
}

//export materialSetLabelColor
func materialSetLabelColor(label int, color int) {
    lbl := LabelStyleRefs.items[label]
    clr := NRGBARefs.items[color]
    lbl.Color = *clr
}

//export windowNextEvent
func windowNextEvent(window int) (int, int) {
    w := WindowRefs.items[window]
    event := w.NextEvent()
    switch e := event.(type) {
    case app.FrameEvent:
        return FrameEventRefs.Add(&e).key, 1
    }
    return -1, -1
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

func (item *ReferenceItem[T]) Free() {
    item.reference.Remove(item.key)
}

var WindowRefs = CreateReference[*app.Window]()
var FrameEventRefs = CreateReference[*app.FrameEvent]()
var ContextRefs = CreateReference[*layout.Context]()
var LabelStyleRefs = CreateReference[*material.LabelStyle]()
var NRGBARefs = CreateReference[*color.NRGBA]()
var ThemeRefs = CreateReference[*material.Theme]()

func main() {
    //window := appNewWindow()
    //theme := materialNewTheme()
    //for {
    //  event, t := windowNextEvent(window)
    //
    //  if event >= 0 && t == 1 {
    //      context := appNewContext(event)
    //      title := materialH1(theme, toStringC("Hello, Gio Ref"))
    //      maroon := colorRgba(127, 0, 0, 255)
    //      materialSetLabelColor(title, maroon)
    //      materialSetLabelAlignment(title, uint8(text.Middle))
    //      materialLabelStyleDrawToContext(title, context)
    //      submit(event, context)
    //  }
    //}
}
