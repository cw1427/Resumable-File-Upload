# -*- coding:utf-8 -*-
from uliweb import settings, functions
from uliweb.contrib.upload import save_file
from subprocess import Popen, PIPE
from zipfile import ZipFile
from uliweb.utils.common import application_path
from models import Transfer
from uliweb.orm import get_model, do_, NotFound

import tempfile
import os
import shutil
import shlex
import json


class Resume(object):

    BASE_STORE_PATH = application_path(settings.TRANSFER.BASEUPLAOD)
    # basePath = os.path.join(application_path(settings.TRANSFER.BASEUPLAOD), request.user.username, resumableIdentifier)

    def __init__(self, **kwargs):
        ''' 记录参数 '''
        self.stdout = ''
        self.stderr = ''
        self.fileQueue = list()

    def start(self):
        """
            start transfer inner ResumeFile list in backend queue
        """


class ResumeFile(object):


    def __init__(self, fileobj=None, fileName=None, resumeObj=None, identify=None, username=None, type=1, totalChunk=None, currentChunk=None):
        self.fileObj = fileobj
        self.resumeObj = resumeObj
        self.identifier = identify
        self.username = username
        self.fileName = fileName
        self.filePath = os.path.join(application_path(settings.TRANSFER.BASEUPLAOD), username, identify)
        self.type = type
        self.totalChunk = totalChunk if totalChunk else 0
        self.currentChunk = currentChunk if currentChunk else 0
        if not os.path.exists(self.filePath):
            os.makedirs(self.filePath)

    def keepFile(self):
        absoluteFileName = "%s%s" % (self.identifier, os.path.splitext(self.fileName)[1])
        fileFullPath = os.path.join(self.filePath, absoluteFileName)
        save_file(fileFullPath, self.fileObj.stream, replace=True, convert=False)
        ResumeFile.saveDB(self.identifier, self.username, self.type, settings.TRANSFERSTATUS.init, fileFullPath, self.fileName)
        return True
#         transferModel = get_model('transfer')
#         transferModel(guid=self.identifier, username=self.username, type=self.type, status=settings.TRANSFERSTATUS.init, path=self.fileFullPath, name=self.fileName).save()

    def keepChunk(self, chunkNum=None):
        chunkFullPath = os.path.join(self.filePath, '%s_%s.temp' % (self.identifier, chunkNum))
        save_file(chunkFullPath, self.fileObj.stream, replace=True, convert=False)
        if (self.currentChunk >= self.totalChunk):
            absoluteFileName = "%s%s" % (self.identifier, os.path.splitext(self.fileName)[1])
            wf = open(os.path.join(self.filePath, absoluteFileName), 'wb')
            for i in range(self.totalChunk):
                if os.path.exists(os.path.join(self.filePath, '%s_%s.temp' % (self.identifier, i + 1))):
                    tempFile = open(os.path.join(self.filePath, '%s_%s.temp' % (self.identifier, i + 1)), 'rb')
                    with open(os.path.join(self.filePath, '%s_%s.temp' % (self.identifier, i + 1)), 'rb') as tempFile:
                        wf.write(tempFile.read())
                    os.remove(os.path.join(self.filePath, '%s_%s.temp' % (self.identifier, i + 1)))
                else:
                    print "----chunk file missing %s_%s.temp" % (self.identifier, i + 1)
                    return json({'status':'err', 'msg':'missing chunk %s' % i + 1}, status=400)
            wf.close()
            ruModel = get_model('resumableUpload')
            ruModel.filter(ruModel.c.guid == self.identifier).update(state=1)
            ResumeFile.saveDB(self.identifier, self.username, 2, settings.TRANSFERSTATUS.init, os.path.join(self.filePath, absoluteFileName), self.fileName)
        return True

    @classmethod
    def saveDB(cls, identifier, username, type, status, path, name):
        transferModel = get_model('transfer')
        transferModel(guid=identifier, username=username, type=type, status=status, path=path, name=name).save()
        return True





