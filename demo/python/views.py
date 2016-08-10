# coding=utf-8
from uliweb import expose, functions, decorators, settings, request, response, \
                   redirect, json
from uliweb.orm import get_model, do_, NotFound
from uliweb import request
import logging
import os
import sys
from _pyio import open
from uliweb.utils.common import application_path
from resume import ResumeFile, Resume
import uuid

mylog = logging.getLogger('bigtransfer')

@expose('/index')
@decorators.require_login
def index():
    if functions.has_permission(request.user, settings.TRANSFER.PERMISSION):
        return redirect('/transfer')
    else:
        error(_('You do not have transfer permission'))


@expose('/transfer', methods=['GET', 'POST'])
@decorators.require_login
@decorators.check_permission(settings.TRANSFER.PERMISSION)
def transfer():
    # 形成form
    if request.method == 'POST':
        return redirect('/transfer')
    response.template = 'transfer/index.html'
    return {'message': 'hello'}


@expose('/normalupload', methods=['POST'])
@decorators.require_login
@decorators.check_permission(settings.TRANSFER.PERMISSION)
def normalupload():
    # 形成form
    print "-------normalupload action invoked"
    ufile = request.files.get('file', None)
    if not ufile:
        return json({'msg':'No file had been upload'}, status=400)
    #----keep the file----
    try:
        rf = ResumeFile(fileobj=ufile, fileName=ufile.filename, identify=uuid.uuid1().hex, username=request.user.username)
        rf.keepFile()
    except Exception as e:
        return json({'msg':'keep file error'}, status=400)

    return json({'msg':'success uploaded file', 'status':'success', 'files':[{'name':rf.fileName}]}, status=200)

@expose('/api/resumeupload', methods=['GET', 'POST'])
@decorators.require_login
def resumeupload():
    """
    breakpoint resume file uploading
    """
    resumableChunkNumber = int(request.params['resumableChunkNumber'])
    resumableChunkSize = int(request.params['resumableChunkSize'])
    resumableIdentifier = request.params['resumableIdentifier']
    resumableTotalChunks = int(request.params['resumableTotalChunks'])
    name = request.params['name']
    ruModel = get_model('resumableUpload')
    resumableUploadObj = ruModel.get(ruModel.c.guid == resumableIdentifier)
    if request.method == 'GET':
        #----check if the chunk has uploaded
        if resumableUploadObj is not None and int(resumableUploadObj.currentChunk) >= resumableChunkNumber:
            return json({'status':'success'}, status=200)
        else:
            return json({'status':'404'}, status=404)
    else:
        try:
            resumableFilename = request.params['resumableFilename']
            # basePath = os.path.join(application_path(settings.TRANSFER.BASEUPLAOD), request.user.username, resumableIdentifier)
            ufile = request.files.get('file', None)
            if not ufile:
                return json({'status':'err', 'msg':'No file had been upload'}, status=400)
            rf = ResumeFile(fileobj=ufile, identify=resumableIdentifier, fileName=resumableFilename, username=request.user.username,
                            totalChunk=resumableTotalChunks, currentChunk=resumableChunkNumber)
            rf.keepChunk(resumableChunkNumber)
            if resumableUploadObj is None:
                ruModel(guid=resumableIdentifier, currentChunk=resumableChunkNumber, totalChunk=resumableTotalChunks, state=0).save()
            else:
                ruModel.filter(ruModel.c.guid == resumableIdentifier).update(currentChunk=resumableChunkNumber)
            return json({'status':'ok'}, status=200)
        except Exception as e:
            return json({'msg':'keep file error'}, status=400)

@expose('/api/checkFileChunkStartOffset', methods=['POST'])
@decorators.require_login
def checkFileChunkStartOffset():
    identify = request.params['identify']
    if identify is None or identify == '':
        return json({'error':'parms error'}, 400);
    print "----identify=%s" % identify
    ruModel = get_model('resumableUpload')
    resumableUploadObj = ruModel.get(ruModel.c.guid == identify)
    return  json({'offset':resumableUploadObj.currentChunk if resumableUploadObj else 0}, status=200)




