#!/apps/subi/sw/bin/python

import re, os, subprocess, sys, tempfile

fileToModify = sys.argv[1]
pdfTkCmd = '/apps/subi/sw/bin/pdftk'

# Dump the existing metadata to a temp file
(handle1,temp1) = tempfile.mkstemp('_pdfMeta.in')
(handle2,temp2) = tempfile.mkstemp('_pdfMeta.out')
try:
  print("Reading metadata from '%s'" % fileToModify)
  subprocess.check_call([pdfTkCmd, fileToModify, 'dump_data', 'output', temp1])
  print("Changing field values to 'X'.")
  with os.fdopen(handle1, "r") as inMeta:
    with os.fdopen(handle2, "w") as outMeta:
      while True:
        line = inMeta.readline()
        if line == "":
          break
        if "InfoValue" in line:
          line = "InfoValue: X\n"
        outMeta.write(line)
  print("Rewriting metadata to '%s'" % fileToModify)
  subprocess.check_call([pdfTkCmd, fileToModify, 'update_info', temp2, 'output', fileToModify+".new"])
  os.rename(fileToModify+".new", fileToModify)
  print("Done.")

finally:
  if os.path.exists(temp1):
    os.remove(temp1)
  if os.path.exists(temp2):
    os.remove(temp2)
