def mergePropertyFiles(in1,in2,outfile)

	in1data = {}
	in2data = {}
	outdata = {}

	File.readlines(in1).each do |line|
		if line.strip! != "" && line.index('=') != nil
	  		first_index = line.index('=')
	  		linekey = line[0..first_index-1]
	  		linetext = line[first_index+1..line.length]
			in1data[linekey] = linetext
	  	end
	end

	File.readlines(in2).each do |line|
		if line.strip! != "" && line.index('=') != nil
	  		first_index = line.index('=')
	  		linekey = line[0..first_index-1]
	  		linetext = line[first_index+1..line.length]
			in2data[linekey] = linetext
	  	end
	end

	# Write all entries from the old file that still exist in the new file.
	# This removes entries that are no longer used.
	in1data.each {
		|key,text|
		if in2data.has_key?(key)
			outdata[key] = text
		end
	}

	# Write all new entries that do not yet exist in the old file.
	in2data.each {
		|key,text|
		if !in1data.has_key?(key)
			outdata[key] = text
		end
	}

	File.open(outfile, "w") do |f|
		outdata.each {
			|key,text|
			f.puts key + "=" + text
		}
	end

	`sort #{outfile} -o #{outfile}`

end

mergePropertyFiles(ARGV[0],ARGV[1],ARGV[2])