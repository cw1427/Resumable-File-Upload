# coding=utf8

from uliweb.orm import *
from uliweb.utils.common import get_var
from uliweb import settings
import datetime

class ResumableUpload(Model):
    """
        keep the chunk for the breakpoint resume
    """
    guid = Field(str, max_length=80, index=True)
    currentChunk = Field(str)
    desc = Field(str)
    totalChunk = Field(str)
    state = Field(int)

    def __unicode__(self):
        return '%s' % self.name


class Transfer(Model):
    """
        keep every file transfer instance
    """
    guid = Field(str, max_length=80, index=True)
    desc = Field(str)
    username = Field(str)
    type = Field(int)  #----1=auto build | 2= individual
    status = Field(int)  #----0=init|1=sending|2=finish|3=Success|4=Failed
    path = Field(str)
    name = Field(str)
    createdate = Field(datetime.datetime, auto_now_add=True)

    def __unicode__(self):
        return '%s' % self.name


