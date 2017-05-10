/**
 * Permet de synchroniser un fichier audio avec une vidéo HTML5
 */
;var audiodescription = (function () {
    "use strict";
    var publicStuff = {}, audiodescription = {
        config: {
            defaultAudioType: 'audio/mp3'
        },
        aAudiodescriptionSrc: [],
        aIdVideo: [],
        init: function () {
            var i;
            for (i = 0 ; i < audiodescription.aIdVideo.length ; i++) {
                jwplayer(audiodescription.aIdVideo[i])
                    .onPlay(audiodescription.playAudio)
                    .onPause(audiodescription.pauseAudio)
                    .onBuffer(audiodescription.pauseAudio)
                    .onIdle(audiodescription.pauseAudio)
                    .onSeek(audiodescription.seekAudio)
                    .onVolume(audiodescription.volumeAudio);
                $('<audio>')
                    .attr('id', 'audio_' + audiodescription.aIdVideo[i])
                    .append($('<source>')
                        .attr('src', audiodescription.aAudiodescriptionSrc[i])
                        .attr('type', audiodescription.config.defaultAudioType))
                    .insertAfter('#' + audiodescription.aIdVideo[i]);
                if(document.getElementById('audio_' + audiodescription.aIdVideo[i]).load) {
                    document.getElementById('audio_' + audiodescription.aIdVideo[i]).load();
                }
            }
        },
        addAudioDescription: function (idVideo, audiodescriptionSrc) {
            audiodescription.aIdVideo.push(idVideo);
            audiodescription.aAudiodescriptionSrc.push(audiodescriptionSrc);
        },
        canReadAudio: function (elmt) {
            var bIsHtml5 = jwplayer(this.id).getRenderingMode() === 'html5',
            bAudioCompatible = audiodescription.getRelatedAudio(elmt).canPlayType(audiodescription.config.defaultAudioType);
            return bIsHtml5 && bAudioCompatible;
        },
        getRelatedAudio: function (elmt) {
            return document.getElementById('audio_' + elmt.id);
        },
        playAudio: function () {
            if(audiodescription.canReadAudio(this)){
                document.getElementById('audio_' + this.id).play();
            }
        },
        pauseAudio: function () {
            if(audiodescription.canReadAudio(this)){
                document.getElementById('audio_' + this.id).pause();
            }
        },
        seekAudio: function (event) {
            if(audiodescription.canReadAudio(this)){
                document.getElementById('audio_' + this.id).currentTime = event.offset;
            }
        },
        volumeAudio: function (event) {
            if(audiodescription.canReadAudio(this)){
                document.getElementById('audio_' + this.id).volume = (event.volume/100);
            }
        }
    };
   $(document).ready(audiodescription.init);
   publicStuff.addAudioDescription = audiodescription.addAudioDescription;
   return publicStuff;
}());