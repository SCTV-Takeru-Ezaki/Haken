import $ from 'jquery'
import underscore from 'underscore'
import Backbone from 'backbone'
import ImageCompressor from '../lib/image-compressor.min.js'
import md5 from 'md5'

compressedFile = 1

$(window).on 'load', ->
	console.log 'load'
	if $('input[name="imageFile"]')
		$('input[name="imageFile"]').on 'change', (e)=>
			console.log "on change",e
			file = e.target.files[0]
			if not file
				false
			new ImageCompressor(file,
				quality: .6
				maxWidth:1024
				maxHeight:1024
				minWidth:512
				minHeight:512
				success:(ret)=>
					$('#preview img').remove()
					url = window.URL or window.webkitURL
					
					r = new FileReader()
					r.readAsDataURL(ret)
					r.onloadend = =>
						$('#preview').prepend('<img src='+r.result+'>') 
						$('input[name="image"]').val(r.result)
						$('#submitButton').click()

#					$('#preview').src = url.createObjectURL(ret)
			)

	else
		img = new ImageTrimmer

