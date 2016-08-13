/*
* MIT Licensed
*/
(function () {
    "use strict";
    var unsigned = function (val) {
        return val >>> 0;
    }
    var ENCODING = 1;

    var toArray = function (data) {
        var d = [];
        for (var i = 0; i < data.length; i++) {
            var x = data.charCodeAt(i);
            for (var a = 0; a < ENCODING; a++) {
                d.push(unsigned(x) & 255);
                x = x >> 8;
            }

        }
        return d;
    };

    if (!String.prototype.toByteArray) {
        String.prototype.toByteArray = function () {
            ///<summary>
            /// UTF8 string to byte array
            ///    </summary>
            ENCODING = 1;
            return toArray(this);
        };
    }

    if (!String.prototype.toUnicodeByteArray) {
        String.prototype.toUnicodeByteArray = function () {
            ///<summary>
            /// Unicode string to byte array
            ///    </summary>
            ENCODING = 2;
            return toArray(this);
        };
    }

    if (!String.prototype.toUTF32ByteArray) {
        String.prototype.toUTF32ByteArray = function () {
            ///<summary>
            /// UTF32 string to byte array
            ///    </summary>
            ENCODING = 4;
            return toArray(this);
        };
    }
    
    if (!String.prototype.toArrayBuffer) {
        String.prototype.toArrayBuffer = function () {
              var buf = new ArrayBuffer(this.length*2); // 2 bytes for each char
              var bufView = new Uint16Array(buf);
              for (var i=0, strLen=this.length; i<strLen; i++) {
                bufView[i] = this.charCodeAt(i);
              }
              return buf;
        };
    }
    
    //----extends resumable.js
    function genMd5(file){
        //----TODO need know the file api about the javascript DOM object File.
        return new Promise(function(resolve, reject) {
            sparkMd5(file).then(function(value){
                resolve(value);
            });
        });
    }
    
    function sparkMd5(file) {
        var blobSlice = File.prototype.slice || File.prototype.mozSlice || File.prototype.webkitSlice,
            chunkSize = 1*1024*1024,                             // Read in chunks of 1MB
            chunks = Math.ceil(file.size / chunkSize),
            currentChunk = 0,
            spark = new SparkMD5.ArrayBuffer(),
            fileReader = new FileReader();
            
        return new Promise(function(resolve, reject) {
            //----async mode call use promise as sync return.
            fileReader.onload = function (e) {
                console.log('read chunk nr', currentChunk + 1, 'of', chunks);
                spark.append(e.target.result);                   // Append array buffer
                
                if (currentChunk == 0 && chunks > 1 )
                    currentChunk = chunks - 2;
                currentChunk++;
                
                if (currentChunk < chunks) {
                    loadNext();
                } else {
                    console.log('finished loading');
                    //----also append file size and file update file
                    spark.append(file.size.toString().toArrayBuffer());
                    spark.append(file.lastModified.toString().toArrayBuffer());
                    var str =  spark.end();
                    console.log(str);
                    resolve(str);
                }
            };
        
            fileReader.onerror = function () {
                console.warn('oops, something went wrong.');
                reject(null);
            };
        
            function loadNext() {
                var start = currentChunk * chunkSize,
                    end = ((start + chunkSize) >= file.size) ? file.size : start + chunkSize;
                fileReader.readAsArrayBuffer(blobSlice.call(file, start, end));
            }
            loadNext();

        });
    }
    
    if (!Resumable.prototype.genMd5) {
        Resumable.prototype.genMd5 = genMd5;
    }

})();
