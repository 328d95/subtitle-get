CREATE TABLE IF NOT EXISTS subtitles (
	subtitleFileId INTEGER PRIMARY KEY,
	imdbId INTEGER NOT NULL,
	subLanguage TEXT,
	subFormat TEXT,
	name TEXT,
	year INTEGER,
	language TEXT,
	type TEXT,
	subEncoding TEXT,
	subForeignPartsOnly BOOLEAN,
	subDownloadLink TEXT,
	seriesImdbParent INTEGER,
	subberName TEXT,
	imdbRating REAL,
	queryDate DATE,
	downloadDate DATE,
	filePath TEXT
);
