
# Datatypes

List of validators and its parameters generated automatically.

## alpha

String with only alphabetical ASCII letters.

Random value example: 'RvLZguAvEmXt'

SQL datatype: VARCHAR(256)

Laravel SQL datatype: string('name', 256)



## alphanum

String with only alphabetical ASCII letters and numbers.

Random value example: 'RRrxmk7xxQ1XJ3'

SQL datatype: VARCHAR(256)

Laravel SQL datatype: string('name', 256)



## alphanumdash

String with only alphabetical ASCII letters, numbers, underscore _ and dash -.

Random value example: 'RwEV694QXft'

SQL datatype: VARCHAR(256)

Laravel SQL datatype: string('name', 256)



## bool

Datatype for boolean values. Accepts actual boolean values, "true"/"false" strings and 0/1 numbers.

Random value example: false

SQL datatype: INT

Laravel SQL datatype: boolean(name)



## boolean

Datatype for boolean values. Accepts actual boolean values, "true"/"false" strings and 0/1 numbers.

Random value example: true

SQL datatype: INT

Laravel SQL datatype: boolean(name)



## cnpj

Datatype for Brazilian CNPJ document numbers.

Random value example: '04.341.543/0001-23'

SQL datatype: VARCHAR(256)

Laravel SQL datatype: string('name', 256)



## color

Datatype for RGB colors in hexadecimeal format, starting with #.

Random value example: '#89C9D2'

SQL datatype: VARCHAR(256)

Laravel SQL datatype: string('name', 256)



## constant

Constant values



## countrycodeiso2

Country names represented by ISO 2-letter codes.

Random value example: 'FK'

SQL datatype: CHAR(2)

Laravel SQL datatype: char('name', 2)



## countrycodeiso3

Country names represented by ISO 3-letter codes.

Random value example: 'DZA'

SQL datatype: CHAR(3)

Laravel SQL datatype: char('name', 3)



## countrycodenumeric

Country names represented by ISO numeric codes.

Random value example: 218

SQL datatype: CHAR(3)

Laravel SQL datatype: char('name', 3)



## cpf

Datatype for Brazilian CPF document numbers.

Random value example: '386.273.345-95'

SQL datatype: VARCHAR(13)

Laravel SQL datatype: string(name, 13)



## currency

Currency names, with their 3-letter codes.

Random value example: 'JMD'

SQL datatype: CHAR(3)

Laravel SQL datatype: char(name, 3)



## date

Dates in ISO format: YYYY-MM-DD.

Random value example: '2029-01-27'

SQL datatype: DATE

Laravel SQL datatype: date



## datetime

Datetimes in ISO8601 format.

Random value example: '2030-02-13T08:49:24+0000'

SQL datatype: DATETIME

Laravel SQL datatype: datetime('name')



## domain

Internet domain names.

Random value example: 'howell.com'

SQL datatype: VARCHAR(256)

Laravel SQL datatype: string('name', 256)



## email

Emails (hopefully, but we use Respect for validation)

Random value example: 'mrippin@gerhold.com'

SQL datatype: VARCHAR(256)

Laravel SQL datatype: string('name', 256)



## file





## float

Floating point numbers.

Random value example: 0.908

SQL datatype: FLOAT

Laravel SQL datatype: float('name')



## html

HTML, validated and sanitized with HTMLPurifier.

Random value example: '<p>HTML <span>Est aspernatur veniam ut. Quo sit provident temporibus eligendi. Est perspiciatis repellat repellat quo eos. Ullam sint reprehenderit ut quidem accusamus ut itaque officiis.</span>Dolore autem non reiciendis. Aut rerum incidunt voluptatem sapiente veritatis. Omnis voluptatem architecto ab qui ratione quam. In laboriosam accusantium quos consequatur omnis ad sunt qui.</p>'

SQL datatype: TEXT

Laravel SQL datatype: text('name')



## integer

Datatype for integers, between -2147483648 and 2147483647.

Random value example: -2039560147

SQL datatype: INT

Laravel SQL datatype: integer("name")



## ip

Strings in UTF-8 and sanitized, up to 256 characters (which might be more than its bytes).

Random value example: '60ea:def:30ec:226a:cabe:9e80:2c14:110b'

SQL datatype: VARCHAR(39)

Laravel SQL datatype: ipAdddress('name')



## ipv4

Datatype for IPs in IPV4 format

Random value example: '181.200.248.234'

SQL datatype: VARCHAR(256)

Laravel SQL datatype: string('name', 256)



## ipv6

Datatype for IPs in IPV6 format

Random value example: '1dca:e4ef:a42b:5941:8c24:4893:74b9:952f'

SQL datatype: VARCHAR(256)

Laravel SQL datatype: string('name', 256)



## json

Valid JSON data

Random value example: '{"version":285012124,"data":{"string":"RowBr","float":0.353}}'

SQL datatype: TEXT

Laravel SQL datatype: text('name')



## language

Languages. Names are in the actual language. This follows wikipedia, prefer 'languageiso2' for an ISO standard.

Random value example: 'ku'

SQL datatype: VARCHAR(10)

Laravel SQL datatype: string(name, 10)



## languageiso2

Languages represented by ISO630-1 2-letter codes.

Random value example: 'nr'

SQL datatype: CHAR(2)

Laravel SQL datatype: char('name', 2)



## phone

A phone number in E164 format

Random value example: '+1124164612196'

SQL datatype: VARCHAR(256)

Laravel SQL datatype: string('name', 256)



## string

Strings in UTF-8 and sanitized, up to 256 characters (which might be more than its bytes).

Random value example: 'RCHqFptDox54A'

SQL datatype: VARCHAR(256)

Laravel SQL datatype: string('name', 256)



## text

Strings in UTF-8 and sanitized, up to 1024000 characters (which might be more than its bytes).

Random value example: 'Voluptatem quia ut doloribus rem qui quia neque. Iste magnam esse odio ut voluptates qui. Voluptatem est magnam molestiae sint illum. Expedita quisquam molestiae voluptatem.'

SQL datatype: TEXT

Laravel SQL datatype: text('name')



## time

Time (HH:MM:SS).

Random value example: '11:46:41'

SQL datatype: TIME

Laravel SQL datatype: time('name', 0)



## timestamp

Timestamps. Just like datetime, but might be a different type in your database.

Random value example: '2013-07-02T07:30:53+0000'

SQL datatype: TIMESTAMP

Laravel SQL datatype: timestamp('name')



## timezone

Timezones. Follows PHP timezone_identifiers_list().

Random value example: 'Europe/Jersey'

SQL datatype: VARCHAR(50)

Laravel SQL datatype: string(name, 50)



## uinteger

Datatype for unsigned integers, between 0 and 4294967296.

Random value example: 2995752285

SQL datatype: INT UNSIGNED

Laravel SQL datatype: integer("name")->unsigned()



## url

Datatype for URLs

Random value example: 'http://hartmann.com/'

SQL datatype: VARCHAR(256)

Laravel SQL datatype: string('name', 256)



## usmall

Datatype for unsigned small integers, between 0 and 65536.

Random value example: 40609

SQL datatype: SMALLINT UNSIGNED

Laravel SQL datatype: smallInteger("name")->unsigned()



## uuid

Datatype for uuid values.

Random value example: 'f660aa64-879f-4894-ab81-7dc02cd41f69'

SQL datatype: CHAR(16)

Laravel SQL datatype: uuid('name')



## year

Valid years. May create a special field in the database.

Random value example: -754083438

SQL datatype: INT

Laravel SQL datatype: year('name')


