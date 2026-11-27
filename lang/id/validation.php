<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => 'Kolom :attribute harus disetujui.',
    'accepted_if' => 'Kolom :attribute harus disetujui jika :other bernilai :value.',
    'active_url' => 'Kolom :attribute harus berupa URL yang valid.',
    'after' => 'Kolom :attribute harus berupa tanggal setelah :date.',
    'after_or_equal' => 'Kolom :attribute harus berupa tanggal setelah atau sama dengan :date.',
    'alpha' => 'Kolom :attribute hanya boleh berisi huruf.',
    'alpha_dash' => 'Kolom :attribute hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
    'alpha_num' => 'Kolom :attribute hanya boleh berisi huruf dan angka.',
    'any_of' => 'Kolom :attribute tidak valid.',
    'array' => 'Kolom :attribute harus berupa daftar.',
    'array_keys' => 'Kolom :attribute hanya boleh memuat kunci berikut: :values.',
    'ascii' => 'Kolom :attribute hanya boleh berisi huruf, angka, dan simbol standar.',
    'base64' => 'Kolom :attribute harus berupa teks Base64 yang valid.',
    'before' => 'Kolom :attribute harus berupa tanggal sebelum :date.',
    'before_or_equal' => 'Kolom :attribute harus berupa tanggal sebelum atau sama dengan :date.',
    'between' => [
        'array' => 'Kolom :attribute harus berisi antara :min sampai :max item.',
        'file' => 'Ukuran :attribute harus antara :min sampai :max kilobyte.',
        'numeric' => 'Kolom :attribute harus bernilai antara :min sampai :max.',
        'string' => 'Kolom :attribute harus berisi antara :min sampai :max karakter.',
    ],
    'boolean' => 'Kolom :attribute harus bernilai ya atau tidak.',
    'can' => 'Kolom :attribute berisi nilai yang tidak diizinkan.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'contains' => 'Kolom :attribute belum memuat nilai yang diwajibkan.',
    'current_password' => 'Kata sandi salah.',
    'date' => 'Kolom :attribute harus berupa tanggal yang valid.',
    'date_equals' => 'Kolom :attribute harus berupa tanggal yang sama dengan :date.',
    'date_format' => 'Kolom :attribute harus mengikuti format :format.',
    'decimal' => 'Kolom :attribute harus memiliki :decimal angka di belakang koma.',
    'declined' => 'Kolom :attribute harus ditolak.',
    'declined_if' => 'Kolom :attribute harus ditolak jika :other bernilai :value.',
    'different' => 'Kolom :attribute dan :other harus berbeda.',
    'digits' => 'Kolom :attribute harus terdiri dari :digits digit.',
    'digits_between' => 'Kolom :attribute harus terdiri dari :min sampai :max digit.',
    'dimensions' => 'Dimensi gambar :attribute tidak valid.',
    'distinct' => 'Kolom :attribute berisi nilai yang sama lebih dari sekali.',
    'doesnt_contain' => 'Kolom :attribute tidak boleh memuat salah satu dari: :values.',
    'doesnt_end_with' => 'Kolom :attribute tidak boleh diakhiri dengan salah satu dari: :values.',
    'doesnt_start_with' => 'Kolom :attribute tidak boleh diawali dengan salah satu dari: :values.',
    'email' => 'Kolom :attribute harus berupa alamat email yang valid.',
    'encoding' => 'Kolom :attribute harus menggunakan encoding :encoding.',
    'ends_with' => 'Kolom :attribute harus diakhiri dengan salah satu dari: :values.',
    'enum' => ':Attribute yang dipilih tidak valid.',
    'exists' => ':Attribute yang dipilih tidak valid.',
    'extensions' => 'Berkas :attribute harus berekstensi salah satu dari: :values.',
    'file' => 'Kolom :attribute harus berupa berkas.',
    'filled' => 'Kolom :attribute harus diisi.',
    'gt' => [
        'array' => 'Kolom :attribute harus berisi lebih dari :value item.',
        'file' => 'Ukuran :attribute harus lebih dari :value kilobyte.',
        'numeric' => 'Kolom :attribute harus lebih besar dari :value.',
        'string' => 'Kolom :attribute harus lebih dari :value karakter.',
    ],
    'gte' => [
        'array' => 'Kolom :attribute harus berisi :value item atau lebih.',
        'file' => 'Ukuran :attribute harus :value kilobyte atau lebih.',
        'numeric' => 'Kolom :attribute harus lebih besar dari atau sama dengan :value.',
        'string' => 'Kolom :attribute harus berisi :value karakter atau lebih.',
    ],
    'hex_color' => 'Kolom :attribute harus berupa kode warna heksadesimal yang valid.',
    'image' => 'Kolom :attribute harus berupa gambar.',
    'in' => ':Attribute yang dipilih tidak valid.',
    'in_array' => 'Kolom :attribute harus ada di dalam :other.',
    'in_array_keys' => 'Kolom :attribute harus memuat setidaknya salah satu kunci berikut: :values.',
    'integer' => 'Kolom :attribute harus berupa bilangan bulat.',
    'ip' => 'Kolom :attribute harus berupa alamat IP yang valid.',
    'ipv4' => 'Kolom :attribute harus berupa alamat IPv4 yang valid.',
    'ipv6' => 'Kolom :attribute harus berupa alamat IPv6 yang valid.',
    'json' => 'Kolom :attribute harus berupa teks JSON yang valid.',
    'list' => 'Kolom :attribute harus berupa daftar berurutan.',
    'lowercase' => 'Kolom :attribute harus menggunakan huruf kecil.',
    'lt' => [
        'array' => 'Kolom :attribute harus berisi kurang dari :value item.',
        'file' => 'Ukuran :attribute harus kurang dari :value kilobyte.',
        'numeric' => 'Kolom :attribute harus lebih kecil dari :value.',
        'string' => 'Kolom :attribute harus kurang dari :value karakter.',
    ],
    'lte' => [
        'array' => 'Kolom :attribute tidak boleh berisi lebih dari :value item.',
        'file' => 'Ukuran :attribute harus :value kilobyte atau kurang.',
        'numeric' => 'Kolom :attribute harus lebih kecil dari atau sama dengan :value.',
        'string' => 'Kolom :attribute harus berisi :value karakter atau kurang.',
    ],
    'mac_address' => 'Kolom :attribute harus berupa alamat MAC yang valid.',
    'max' => [
        'array' => 'Kolom :attribute tidak boleh berisi lebih dari :max item.',
        'file' => 'Ukuran :attribute tidak boleh lebih dari :max kilobyte.',
        'numeric' => 'Kolom :attribute tidak boleh lebih besar dari :max.',
        'string' => 'Kolom :attribute tidak boleh lebih dari :max karakter.',
    ],
    'max_digits' => 'Kolom :attribute tidak boleh lebih dari :max digit.',
    'mimes' => 'Berkas :attribute harus bertipe: :values.',
    'mimetypes' => 'Berkas :attribute harus bertipe: :values.',
    'min' => [
        'array' => 'Kolom :attribute harus berisi minimal :min item.',
        'file' => 'Ukuran :attribute minimal :min kilobyte.',
        'numeric' => 'Kolom :attribute minimal bernilai :min.',
        'string' => 'Kolom :attribute minimal :min karakter.',
    ],
    'min_digits' => 'Kolom :attribute minimal terdiri dari :min digit.',
    'missing' => 'Kolom :attribute tidak boleh dikirim.',
    'missing_if' => 'Kolom :attribute tidak boleh dikirim jika :other bernilai :value.',
    'missing_unless' => 'Kolom :attribute tidak boleh dikirim kecuali :other bernilai :value.',
    'missing_with' => 'Kolom :attribute tidak boleh dikirim jika :values diisi.',
    'missing_with_all' => 'Kolom :attribute tidak boleh dikirim jika :values diisi.',
    'multiple_of' => 'Kolom :attribute harus kelipatan dari :value.',
    'not_in' => ':Attribute yang dipilih tidak valid.',
    'not_regex' => 'Format :attribute tidak valid.',
    'numeric' => 'Kolom :attribute harus berupa angka.',
    'password' => [
        'letters' => 'Kolom :attribute harus memuat minimal satu huruf.',
        'mixed' => 'Kolom :attribute harus memuat minimal satu huruf besar dan satu huruf kecil.',
        'numbers' => 'Kolom :attribute harus memuat minimal satu angka.',
        'symbols' => 'Kolom :attribute harus memuat minimal satu simbol.',
        'uncompromised' => ':Attribute ini pernah muncul dalam kebocoran data. Silakan gunakan :attribute lain.',
    ],
    'present' => 'Kolom :attribute harus dikirim.',
    'present_if' => 'Kolom :attribute harus dikirim jika :other bernilai :value.',
    'present_unless' => 'Kolom :attribute harus dikirim kecuali :other bernilai :value.',
    'present_with' => 'Kolom :attribute harus dikirim jika :values diisi.',
    'present_with_all' => 'Kolom :attribute harus dikirim jika :values diisi.',
    'prohibited' => 'Kolom :attribute tidak boleh diisi.',
    'prohibited_if' => 'Kolom :attribute tidak boleh diisi jika :other bernilai :value.',
    'prohibited_if_accepted' => 'Kolom :attribute tidak boleh diisi jika :other disetujui.',
    'prohibited_if_declined' => 'Kolom :attribute tidak boleh diisi jika :other ditolak.',
    'prohibited_unless' => 'Kolom :attribute tidak boleh diisi kecuali :other bernilai salah satu dari :values.',
    'prohibits' => 'Jika :attribute diisi, :other tidak boleh diisi.',
    'regex' => 'Format :attribute tidak valid.',
    'required' => 'Kolom :attribute wajib diisi.',
    'required_array_keys' => 'Kolom :attribute harus memuat: :values.',
    'required_if' => 'Kolom :attribute wajib diisi jika :other bernilai :value.',
    'required_if_accepted' => 'Kolom :attribute wajib diisi jika :other disetujui.',
    'required_if_declined' => 'Kolom :attribute wajib diisi jika :other ditolak.',
    'required_unless' => 'Kolom :attribute wajib diisi kecuali :other bernilai salah satu dari :values.',
    'required_with' => 'Kolom :attribute wajib diisi jika :values diisi.',
    'required_with_all' => 'Kolom :attribute wajib diisi jika :values diisi.',
    'required_without' => 'Kolom :attribute wajib diisi jika :values tidak diisi.',
    'required_without_all' => 'Kolom :attribute wajib diisi jika :values tidak ada yang diisi.',
    'same' => 'Kolom :attribute harus sama dengan :other.',
    'size' => [
        'array' => 'Kolom :attribute harus berisi :size item.',
        'file' => 'Ukuran :attribute harus :size kilobyte.',
        'numeric' => 'Kolom :attribute harus bernilai :size.',
        'string' => 'Kolom :attribute harus berisi :size karakter.',
    ],
    'starts_with' => 'Kolom :attribute harus diawali dengan salah satu dari: :values.',
    'string' => 'Kolom :attribute harus berupa teks.',
    'timezone' => 'Kolom :attribute harus berupa zona waktu yang valid.',
    'unique' => ':Attribute sudah digunakan.',
    'uploaded' => ':Attribute gagal diunggah.',
    'uppercase' => 'Kolom :attribute harus menggunakan huruf besar.',
    'url' => 'Kolom :attribute harus berupa URL yang valid.',
    'ulid' => 'Kolom :attribute harus berupa ULID yang valid.',
    'uuid' => 'Kolom :attribute harus berupa UUID yang valid.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        'name' => 'nama',
        'username' => 'nama pengguna',
        'email' => 'email',
        'password' => 'kata sandi',
        'password_confirmation' => 'konfirmasi kata sandi',
        'current_password' => 'kata sandi saat ini',
        'role' => 'role',
        'role_id' => 'role',
        'permission' => 'permission',
        'code' => 'kode',
        'recovery_code' => 'kode pemulihan',
    ],

];
