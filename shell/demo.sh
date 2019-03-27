#!/bin/bash
read a
read b
if (( $a == $b ))
then
    echo "相等"
else
    echo "a和b不相等，输入错误"
fi
