package main

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

func main() {

}
