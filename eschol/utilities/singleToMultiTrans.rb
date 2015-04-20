#!/usr/bin/env ruby

# Translate an SQL dump with multiple lines for each table to a single line per table,
# for vastly greater restore speed.

prevCmd = nil
count = 0
File.open(ARGV[0], "r:UTF-8") do |io|
  io.each { |line|
    if line =~ /^(INSERT INTO [^ ]+ VALUES )(.+);$/
      cmd, val = $1, $2
      if prevCmd
        if cmd == prevCmd && count < 500
          count += 1
          STDOUT.write(",\n" + val)
        else
          STDOUT.write(";\n" + cmd + val)
          count = 0
        end
      else
        STDOUT.write(cmd + val)
      end
      prevCmd = cmd
    else
      if prevCmd
        STDOUT.write(";\n")
        prevCmd = nil
        count = 0
      end
      puts line
    end
  }
end