#!/usr/bin/env ruby

# Translate an SQL dump with multiple lines for each table to a single line per table,
# for vastly greater restore speed.

open(ARGV[1], "r", "UTF-8") do |io|
  io.each { |line|
    puts line
  }
end