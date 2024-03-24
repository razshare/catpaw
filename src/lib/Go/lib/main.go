package main

import "C"
import (
    "fmt"
    "log"

    "github.com/jroimartin/gocui"
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
    index: 1,
}

func ref(item any) uint64 {
    key := global.index
    global.index++
    global.items[key] = &item
    return key
}

func unref[T any](key uint64) *T {
    item := global.items[key]
    result := (*item).(T)
    return &result
}

// #################################
// #################################
// #################################
// #################################
// #################################
// ======================[START]===> Utilities

//export Destroy
func Destroy(key uint64) {
    delete(global.items, key)
}

// Library stuff

//export MaxX
func MaxX() int {
    return maxX
}

//export MaxY
func MaxY() int {
    return maxY
}

//export NewView
func NewView(name stringC, x0 int, y0 int, x1 int, y1 int) uint64 {
    v, err := g.SetView(toString(name), x0, y0, x1, y1)
    if err != gocui.ErrUnknownView {
        return 0
    }

    return ref(v)
}

//export Fprintln
func Fprintln(view uint64, content stringC) {
    v := *unref[*gocui.View](view)
    fmt.Fprintln(v, toString(content))
}

var g *gocui.Gui

//export NewGui
func NewGui() {
    gui, err := gocui.NewGui(gocui.OutputNormal)
    if err != nil {
        log.Panicln(err)
    }
    g = gui
}

//export Update
func Update(increase uint64) uint64 {
    update += increase
    return update
}

var maxX = 0
var maxY = 0
var update uint64 = 0

//export StartGui
func StartGui() {
    go func() {
        defer g.Close()

        g.SetManagerFunc(func(g *gocui.Gui) error {
            x, y := g.Size()
            update++
            maxX = x
            maxY = y
            return nil
        })

        if err := g.SetKeybinding(
            "",
            gocui.KeyCtrlC,
            gocui.ModNone,
            func(g *gocui.Gui, v *gocui.View) error {
                return gocui.ErrQuit
            },
        ); err != nil {
            log.Panicln(err)
        }

        if err := g.MainLoop(); err != nil && err != gocui.ErrQuit {
            log.Panicln(err)
        }
    }()
}

func main() {

}
