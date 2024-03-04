package main

import "C"

func main() {}

//export hello
func hello(name *C.char) *C.char {
    return C.CString("hello " + C.GoString(name))
}
