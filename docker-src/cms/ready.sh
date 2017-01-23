#!/bin/bash

READY=FALSE
while [ "$READY" != "TRUE" ]
do
  if docker logs --tail 10 bg-sync 2>&1 | grep "Nothing to do" >/dev/null 2>&1 ; then
    READY=TRUE
  fi
done
