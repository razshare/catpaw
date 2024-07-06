package main

import (
	"image/png"
	"os"

	"github.com/kbinani/screenshot"
)
import "C"

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

// Custom stuff

//export CaptureScreen
func CaptureScreen(fileNameC stringC) {
	fileName := toString(fileNameC)
	n := screenshot.NumActiveDisplays()

	for i := 0; i < n; i++ {
		bounds := screenshot.GetDisplayBounds(i)

		img, err := screenshot.CaptureRect(bounds)
		if err != nil {
			panic(err)
		}
		file, _ := os.Create(fileName)
		defer file.Close()
		png.Encode(file, img)
	}
}

func main() {
}
