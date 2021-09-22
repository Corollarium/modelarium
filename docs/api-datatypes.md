# Datatypes Referece

List of validators and its parameters generated automatically.

## alpha

String with only alphabetical ASCII letters.

Random value example: 'RiJsUbgs'

SQL datatype: VARCHAR(256)

Laravel SQL datatype: string('name', 256)

## alphanum

String with only alphabetical ASCII letters and numbers.

Random value example: 'R65G6tkQ2T'

SQL datatype: VARCHAR(256)

Laravel SQL datatype: string('name', 256)

## alphanumdash

String with only alphabetical ASCII letters, numbers, underscore \_ and dash -.

Random value example: 'RnukkJS-cZinbmJ'

SQL datatype: VARCHAR(256)

Laravel SQL datatype: string('name', 256)

## bool

Datatype for boolean values. Accepts actual boolean values, "true"/"false" strings and 0/1 numbers.

Random value example: false

SQL datatype: INT

Laravel SQL datatype: boolean(name)

## boolean

Datatype for boolean values. Accepts actual boolean values, "true"/"false" strings and 0/1 numbers.

Random value example: false

SQL datatype: INT

Laravel SQL datatype: boolean(name)

## cnpj

Datatype for Brazilian CNPJ document numbers.

Random value example: '80.585.205/0001-15'

SQL datatype: VARCHAR(256)

Laravel SQL datatype: string('name', 256)

## color

Datatype for RGB colors in hexadecimeal format, starting with #.

Random value example: '#DE2CB9'

SQL datatype: VARCHAR(256)

Laravel SQL datatype: string('name', 256)

## constant

Constant values

## countrycodeiso2

Country names represented by ISO 2-letter codes.

Random value example: 'CV'

SQL datatype: CHAR(2)

Laravel SQL datatype: char('name', 2)

## countrycodeiso3

Country names represented by ISO 3-letter codes.

Random value example: 'YMD'

SQL datatype: CHAR(3)

Laravel SQL datatype: char('name', 3)

## countrycodenumeric

Country names represented by ISO numeric codes.

Random value example: 398

SQL datatype: CHAR(3)

Laravel SQL datatype: char('name', 3)

## cpf

Datatype for Brazilian CPF document numbers.

Random value example: '914.998.711-98'

SQL datatype: VARCHAR(13)

Laravel SQL datatype: string(name, 13)

## currency

Currency names, with their 3-letter codes.

Random value example: 'BGN'

SQL datatype: CHAR(3)

Laravel SQL datatype: char(name, 3)

## date

Dates in ISO format: YYYY-MM-DD.

Random value example: '2028-09-20'

SQL datatype: DATE

Laravel SQL datatype: date

## datetime

Datetimes in ISO8601 format.

Random value example: '2026-01-03T19:24:55-0300'

SQL datatype: DATETIME

Laravel SQL datatype: datetime('name')

## domain

Internet domain names.

Random value example: 'okon.biz'

SQL datatype: VARCHAR(256)

Laravel SQL datatype: string('name', 256)

## email

Emails (hopefully, but we use Respect for validation)

Random value example: 'davis.brent@hotmail.com'

SQL datatype: VARCHAR(256)

Laravel SQL datatype: string('name', 256)

## file

## float

Floating point numbers.

Random value example: 0.123

SQL datatype: FLOAT

Laravel SQL datatype: float('name')

## html

HTML, validated and sanitized with HTMLPurifier.

Random value example: '<p>HTML <span>Quis delectus ut vel error. Voluptatem fugiat nulla magni voluptatem. Aut eaque error autem. Animi aut ullam eveniet accusamus.</span>Consequatur consequuntur dolore maxime consectetur nesciunt earum ullam. Hic ab tempore voluptatem iusto non qui consequatur. Est ullam pariatur qui dicta.</p>'

SQL datatype: TEXT

Laravel SQL datatype: text('name')

## integer

Datatype for integers, between -2147483648 and 2147483647.

Random value example: 545132295

SQL datatype: INT

Laravel SQL datatype: integer("name")

## ip

Strings in UTF-8 and sanitized, up to 256 characters (which might be more than its bytes).

Random value example: '133.232.127.115'

SQL datatype: VARCHAR(39)

Laravel SQL datatype: ipAdddress('name')

## ipv4

Datatype for IPs in IPV4 format

Random value example: '148.121.67.59'

SQL datatype: VARCHAR(256)

Laravel SQL datatype: string('name', 256)

## ipv6

Datatype for IPs in IPV6 format

Random value example: 'aff9:78c9:911f:dd78:c486:7388:b351:d31a'

SQL datatype: VARCHAR(256)

Laravel SQL datatype: string('name', 256)

## json

Valid JSON data

Random value example: '{"version":-1645684042,"data":{"string":"RHGJ2Ty","float":0.982}}'

SQL datatype: TEXT

Laravel SQL datatype: text('name')

## language

Languages. Names are in the actual language. Codes follow ISO 639-1 codes.

Random value example: 'et'

SQL datatype: VARCHAR(10)

Laravel SQL datatype: string(name, 10)

## phone

A phone number in E164 format

Random value example: '+5412038893160'

SQL datatype: VARCHAR(256)

Laravel SQL datatype: string('name', 256)

## string

Strings in UTF-8 and sanitized, up to 256 characters (which might be more than its bytes).

Random value example: 'RhHe6zghDQGx'

SQL datatype: VARCHAR(256)

Laravel SQL datatype: string('name', 256)

## text

Strings in UTF-8 and sanitized, up to 1024000 characters (which might be more than its bytes).

Random value example: 'Est perferendis tempora accusamus voluptatem earum asperiores. Deserunt sed enim consequuntur ducimus culpa. Deserunt eos asperiores harum culpa.'

SQL datatype: TEXT

Laravel SQL datatype: text('name')

## time

Time (HH:MM:SS).

Random value example: '15:16:56'

SQL datatype: TIME

Laravel SQL datatype: time('name', 0)

## timestamp

Timestamps. Just like datetime, but might be a different type in your database.

Random value example: '2011-07-19T14:01:40-0300'

SQL datatype: TIMESTAMP

Laravel SQL datatype: timestamp('name')

## timezone

Timezones. Follows PHP timezone_identifiers_list().

Random value example: 'Africa/Windhoek'

SQL datatype: VARCHAR(50)

Laravel SQL datatype: string(name, 50)

## uinteger

Datatype for unsigned integers, between 0 and 4294967296.

Random value example: 767598853

SQL datatype: INT UNSIGNED

Laravel SQL datatype: integer("name")->unsigned()

## url

Datatype for URLs

Random value example: 'http://www.vandervort.com/sit-ea-excepturi-similique-sit-sequi.html'

SQL datatype: VARCHAR(256)

Laravel SQL datatype: string('name', 256)

## usmall

Datatype for unsigned small integers, between 0 and 65536.

Random value example: 53473

SQL datatype: SMALLINT UNSIGNED

Laravel SQL datatype: smallInteger("name")->unsigned()

## uuid

Datatype for uuid values.

Random value example: '40930740-56d7-4981-8bda-e0a9ba2f044c'

SQL datatype: CHAR(16)

Laravel SQL datatype: uuid('name')

## year

Valid years. May create a special field in the database.

Random value example: 935433446

SQL datatype: INT

Laravel SQL datatype: year('name')
