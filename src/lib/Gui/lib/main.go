package main

import "C"
import (
    "image"
    "image/color"
    "os"

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
func context(operations int, frameEvent int) int {
    ops := OperationsRefs.items[operations]
    fe := FrameEventRefs.items[frameEvent]
    ctx := app.NewContext(ops, *fe)
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
func pathStart(operations int, x float32, y float32) int {
    ops := OperationsRefs.items[operations]
    var p clip.Path
    p.Begin(ops)
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
func pathEnd(operations int, line int, width float32, clr int) {
    ops := OperationsRefs.items[operations]
    p := PathRefs.items[line]
    c := NRGBARefs.items[clr]
    spec := p.End()

    paint.FillShape(ops, *c,
        clip.Stroke{
            Path:  spec,
            Width: width,
        }.Op(),
    )
}

//export openFile
func openFile(fileNameC stringC) int {
    file, error := os.Open(toString(fileNameC))
    if error != nil {
        return -1
    }
    return GoFileRefs.Add(file).key
}

type GoImage struct {
    image  *image.Image
    format stringC
}

//export decodeImage
func decodeImage(goFile int) int {
    f := GoFileRefs.items[goFile]
    image, format, error := image.Decode(f)
    if error != nil {
        return -1
    }

    wrapper := GoImage{
        image:  &image,
        format: toStringC(format),
    }

    return GoImageRefs.Add(&wrapper).key
}

//export addImage
func addImage(operations int, goImage int) {
    ops := OperationsRefs.items[operations]
    goImageRef := GoImageRefs.items[goImage]

    imageOp := paint.NewImageOp(*goImageRef.image)
    imageOp.Filter = paint.FilterNearest
    imageOp.Add(ops)

    paint.PaintOp{}.Add(ops)
}

//export scale
func scale(operations int, originX float32, originY float32, factorX float32, factorY float32) {
    ops := OperationsRefs.items[operations]
    base := f32.Affine2D{}
    aff := base.Scale(f32.Pt(originX, originY), f32.Pt(factorX, factorY))
    op.Affine(aff).Add(ops)
}

//export rotate
func rotate(operations int, originX float32, originY float32, radians float32) {
    ops := OperationsRefs.items[operations]
    base := f32.Affine2D{}
    aff := base.Rotate(f32.Pt(originX, originY), radians)
    op.Affine(aff).Add(ops)
}

//export offset
func offset(operations int, originX float32, originY float32) {
    ops := OperationsRefs.items[operations]
    base := f32.Affine2D{}
    aff := base.Offset(f32.Pt(originX, originY))
    op.Affine(aff).Add(ops)
}

//export shear
func shear(operations int, originX float32, originY float32, radiansX float32, radiansY float32) {
    ops := OperationsRefs.items[operations]
    base := f32.Affine2D{}
    aff := base.Shear(f32.Pt(originX, originY), radiansX, radiansY)
    op.Affine(aff).Add(ops)
}

//export operations
func operations() int {
    var ops op.Ops
    return OperationsRefs.Add(&ops).key
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
func reset(operations int) {
    ops := OperationsRefs.items[operations]
    ops.Reset()
}

//export draw
func draw(operations int, frameEvent int) {
    ops := OperationsRefs.items[operations]
    e := FrameEventRefs.items[frameEvent]
    e.Frame(ops)
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
const RefGoFile = 6
const RefGoImage = 7
const RefOperations = 8

//export destroy
func destroy(refKey int, refType int) {
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
    case RefGoFile:
        file := GoFileRefs.items[refKey]
        file.Close()
        GoFileRefs.Remove(refKey)
    case RefGoImage:
        GoImageRefs.Remove(refKey)
    case RefOperations:
        OperationsRefs.Remove(refKey)
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
var GoFileRefs = CreateReference[*os.File]()
var GoImageRefs = CreateReference[*GoImage]()
var OperationsRefs = CreateReference[*op.Ops]()

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
