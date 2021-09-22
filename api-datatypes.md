
---
nav_order: 12
---

# Datatype reference

List of datatypes and its parameters generated automatically.

## alpha

String with only alphabetical ASCII letters.

Random value example: 'RPGEvzQzPrvCXB'



## alphanum

String with only alphabetical ASCII letters and numbers.

Random value example: 'R7RCVdwFNg'



## alphanumdash

String with only alphabetical ASCII letters, numbers, underscore _ and dash -.

Random value example: 'R0UM4UIH6'



## bool

Datatype for boolean values. Accepts actual boolean values, "true"/"false" strings and 0/1 numbers.

Random value example: false



## boolean

Datatype for boolean values. Accepts actual boolean values, "true"/"false" strings and 0/1 numbers.

Random value example: false



## cnpj

Datatype for Brazilian CNPJ document numbers.

Random value example: '92.205.300/0001-25'



## color

Datatype for RGB colors in hexadecimeal format, starting with #.

Random value example: '#5B0967'



## constant

Constant values



## countrycodeiso2

Country names represented by ISO 2-letter codes.

Random value example: 'IL'



## countrycodeiso3

Country names represented by ISO 3-letter codes.

Random value example: 'GRC'



## countrycodenumeric

Country names represented by ISO numeric codes.

Random value example: 887



## cpf

Datatype for Brazilian CPF document numbers.

Random value example: '603.162.754-09'



## currency

Currency names, with their 3-letter codes.

Random value example: 'UAH'



## date

Dates in ISO format: YYYY-MM-DD.

Random value example: '2024-08-02'



## datetime

Datetimes in ISO8601 format.

Random value example: '2031-07-16T08:18:45-0300'



## domain

Internet domain names.

Random value example: 'torp.info'



## email

Emails (hopefully, but we use Respect for validation)

Random value example: 'qkris@anderson.net'



## file





## float

Floating point numbers.

Random value example: 0.637



## html

HTML, validated and sanitized with HTMLPurifier.

Random value example: '<p>HTML <span>Veniam voluptatem perferendis magnam sit beatae voluptate. Veniam reprehenderit reiciendis fuga consequatur. Tempore et eos ad. Vero expedita sint et error harum.</span>Eveniet vel itaque dolorem quod officia rerum debitis. Nesciunt velit ipsum fugiat. Sint nesciunt perspiciatis accusamus quos. Esse repudiandae temporibus reiciendis autem repellat.</p>'



## integer

Datatype for integers, between -2147483648 and 2147483647.

Random value example: 1698474726



## ip

Strings in UTF-8 and sanitized, up to 39 characters (which might be more than its bytes).

Random value example: 'a8d:c06e:bc52:f9cf:33b7:8686:3ce3:66a0'



## ipv4

Datatype for IPs in IPV4 format

Random value example: '147.131.228.120'



## ipv6

Datatype for IPs in IPV6 format

Random value example: '23b6:5392:2cec:2fb4:5be3:e568:dc32:a95'



## json

Valid JSON data

Random value example: '{"version":-1026945324,"data":{"string":"RQ9Zci9lpNbxpGAHLh8Qnvvzzccej8","float":0.966}}'



## language

Languages. Names are in the actual language. This follows wikipedia, prefer 'languageiso2' for an ISO standard.

Random value example: 'ht'



## languageiso2

Languages represented by ISO630-1 2-letter codes.

Random value example: 'lg'



## name

Just a plain string, but that expects a name. Generates good random names.

Random value example: 'Prof. Conrad Ankunding'



## phone

A phone number in E164 format

Random value example: '+15409215003'



## string

Strings in UTF-8 and sanitized, up to 256 characters (which might be more than its bytes).

Random value example: 'R1Aa6'



## text

Long text in UTF-8 and sanitized, up to 1024000 characters (which might be more than its bytes).

Random value example: 'Id dolorem assumenda voluptatem est tenetur ab. Animi enim aut ullam possimus tempora aut. Aperiam nemo reiciendis quis eos. Cum minima voluptas perferendis voluptates.'



## time

Time (HH:MM:SS).

Random value example: '17:42:29'



## timestamp

Timestamps. Just like datetime, but might be a different type in your database.

Random value example: '2014-10-13T02:22:07-0300'



## timezone

Timezones. Follows PHP timezone_identifiers_list().

Random value example: 'Europe/Istanbul'



## uinteger

Datatype for unsigned integers, between 0 and 4294967296.

Random value example: 1899136886



## url

Datatype for URLs

Random value example: 'http://mcdermott.org/sunt-magnam-repellendus-ipsum-voluptatem'



## usmall

Datatype for unsigned small integers, between 0 and 65536.

Random value example: 35275



## uuid

Datatype for uuid values.

Random value example: '1e543e7e-d2a6-4d42-bce8-1b5b378e62dc'



## year

Valid years. May create a special field in the database.

Random value example: 2005


