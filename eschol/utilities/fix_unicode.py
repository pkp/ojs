#!/usr/bin/env python

""" Search for and fix double-UTF-8 encoding that crept in from our bepress import """

import codecs, os, re, string, subprocess, sys

###############################################################################
def system(cmd):
  #print(cmd)
  proc = subprocess.Popen(cmd, shell=True)
  proc.communicate()
  if proc.returncode != 0:
    print("*** Failed command: " + cmd)
    raise Exception, cmd.split()[0] + " returned non-zero code " + str(proc.returncode)
  else:
    return proc.returncode

################################################################################
def utfDoubleDecode(suspData):
  """ Decoded suspected double-encoded UTF-8 characters. """

  byteStr = string.join([chr(ord(char)) for char in suspData], "")
  return unicode(byteStr, 'UTF-8', 'strict')


###############################################################################
def process(table, idcol, fix):
  sqlCmd = "mysql --defaults-extra-file=/apps/subi/.passwords/ojs_db_pw.mysql"
  sqlFile = "tmp.sql"
  with open(sqlFile, "w") as f:
    f.write("select %s, first_name, middle_name, last_name from %s;" % (idcol, table))
  outFile = "tmp.out"
  system("%s < %s > %s" % (sqlCmd, sqlFile, outFile))

  utfPat = re.compile("[\xC0-\xF4][\x80-\xBF]+")

  # Process each line
  with codecs.open(outFile, "r", "utf-8") as f:
    lines = f.readlines()
    fields = lines[0].strip().split("\t")
    for line in lines[1:]:
      if not utfPat.search(line):
        continue
      decLine = utfDoubleDecode(line)
      fromPairs = dict(zip(fields, line.strip().split("\t")))
      toPairs = dict(zip(fields, decLine.strip().split("\t")))
      for key in fields:
        if fromPairs[key] == toPairs[key]: continue
        cmd = "update %s set %s = '%s' where %s = %s and %s = '%s';" % \
              (table, key, toPairs[key], idcol, fromPairs[idcol], key, fromPairs[key])
        print("  " + cmd)
        fix.write(cmd + "\n")


###############################################################################
if __name__ == '__main__':
  """ The main module. """

  print("Proposed changes:")
  fixFile = "tmp_fix.sql"
  with codecs.open(fixFile, "w", "utf-8") as fix:
    fix.write("begin;\n")
    process("authors", "author_id", fix)
    process("users", "user_id", fix)
    fix.write("rollback;\n")

  print("If you want to run this, edit tmp_fix.sql, change 'rollback' to 'commit', and pipe to gomysql")
