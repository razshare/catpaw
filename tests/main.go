package main

import "C"

func main() {}

//export hello
func hello(name string) *C.char {
    return C.CString("hello " + name)
}
