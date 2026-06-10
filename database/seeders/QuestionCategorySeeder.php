<?php

namespace Database\Seeders;

use App\Models\QuestionCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class QuestionCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        QuestionCategory::create([
            'name' => 'Soal Teori Light Vehicle',
            'description' => 'Kategori soal yang berfokus pada aspek-aspek keselamatan terkait kendaraan ringan, seperti mobil penumpang, kendaraan kecil, atau kendaraan komersial ringan.',
            'measured' => 'Aspek-aspek keselamatan yang diukur dalam kategori ini meliputi pengetahuan tentang peraturan lalu lintas, penggunaan sabuk pengaman, pemahaman tentang tanda-tanda lalu lintas, dan kesadaran akan bahaya yang terkait dengan kendaraan ringan.',
            'measurable' => 'Kategori ini dapat diukur melalui tes tertulis atau ujian teori yang mencakup pertanyaan-pertanyaan terkait keselamatan kendaraan ringan, serta penilaian terhadap pemahaman peserta tentang aspek-aspek keselamatan yang relevan.'
        ]);

        QuestionCategory::create([
            'name' => 'Soal Teori Rambu-rambu',
            'description' => 'Kategori soal yang berfokus pada aspek-aspek keselamatan terkait rambu-rambu lalu lintas, termasuk rambu-rambu peringatan, rambu-rambu larangan, rambu-rambu petunjuk, dan rambu-rambu lalu lintas lainnya.',
            'measured' => 'Aspek-aspek keselamatan yang diukur dalam kategori ini meliputi pengetahuan tentang arti dan fungsi berbagai jenis rambu-rambu lalu lintas, kemampuan untuk mengenali rambu-rambu dengan cepat, serta pemahaman tentang bagaimana rambu-rambu tersebut mempengaruhi perilaku pengemudi dan keselamatan di jalan.',
            'measurable' => 'Kategori ini dapat diukur melalui tes tertulis atau ujian teori yang mencakup pertanyaan-pertanyaan terkait rambu-rambu lalu lintas, serta penilaian terhadap kemampuan peserta untuk mengenali dan memahami arti dari berbagai jenis rambu-rambu yang umum digunakan di jalan raya.'
        ]);

        QuestionCategory::create([
            'name' => 'Soal Teori Dump Truck',
            'description' => 'Kategori soal yang berfokus pada aspek-aspek keselamatan terkait dump truck, termasuk operasional, pemeliharaan, dan prosedur keselamatan saat mengemudi.',
            'measured' => 'Aspek-aspek keselamatan yang diukur dalam kategori ini meliputi pengetahuan tentang peraturan lalu lintas, penggunaan sabuk pengaman, pemahaman tentang tanda-tanda lalu lintas, dan kesadaran akan bahaya yang terkait dengan dump truck.',
            'measurable' => 'Kategori ini dapat diukur melalui tes tertulis atau ujian teori yang mencakup pertanyaan-pertanyaan terkait keselamatan dump truck, serta penilaian terhadap pemahaman peserta tentang aspek-aspek keselamatan yang relevan.'
        ]);

        QuestionCategory::create([
            'name' => 'Soal Teori Excavator',
            'description' => 'Kategori soal yang berfokus pada aspek-aspek keselamatan terkait excavator, termasuk operasional, pemeliharaan, dan prosedur keselamatan saat mengemudi.',
            'measured' => 'Aspek-aspek keselamatan yang diukur dalam kategori ini meliputi pengetahuan tentang peraturan lalu lintas, penggunaan sabuk pengaman, pemahaman tentang tanda-tanda lalu lintas, dan kesadaran akan bahaya yang terkait dengan excavator.',
            'measurable' => 'Kategori ini dapat diukur melalui tes tertulis atau ujian teori yang mencakup pertanyaan-pertanyaan terkait keselamatan excavator, serta penilaian terhadap pemahaman peserta tentang aspek-aspek keselamatan yang relevan.'
        ]);

        QuestionCategory::create([
            'name' => 'Soal Teori Bulldozer',
            'description' => 'Kategori soal yang berfokus pada aspek-aspek keselamatan terkait bulldozer, termasuk operasional, pemeliharaan, dan prosedur keselamatan saat mengemudi.',
            'measured' => 'Aspek-aspek keselamatan yang diukur dalam kategori ini meliputi pengetahuan tentang peraturan lalu lintas, penggunaan sabuk pengaman, pemahaman tentang tanda-tanda lalu lintas, dan kesadaran akan bahaya yang terkait dengan bulldozer.',
            'measurable' => 'Kategori ini dapat diukur melalui tes tertulis atau ujian teori yang mencakup pertanyaan-pertanyaan terkait keselamatan bulldozer, serta penilaian terhadap pemahaman peserta tentang aspek-aspek keselamatan yang relevan.'
        ]);

        QuestionCategory::create([
            'name' => 'Soal Teori Motorgrader',
            'description' => 'Kategori soal yang berfokus pada aspek-aspek keselamatan terkait motorgrader, termasuk operasional, pemeliharaan, dan prosedur keselamatan saat mengemudi.',
            'measured' => 'Aspek-aspek keselamatan yang diukur dalam kategori ini meliputi pengetahuan tentang peraturan lalu lintas, penggunaan sabuk pengaman, pemahaman tentang tanda-tanda lalu lintas, dan kesadaran akan bahaya yang terkait dengan motorgrader.',
            'measurable' => 'Kategori ini dapat diukur melalui tes tertulis atau ujian teori yang mencakup pertanyaan-pertanyaan terkait keselamatan motorgrader, serta penilaian terhadap pemahaman peserta tentang aspek-aspek keselamatan yang relevan.'
        ]);
    }
}
