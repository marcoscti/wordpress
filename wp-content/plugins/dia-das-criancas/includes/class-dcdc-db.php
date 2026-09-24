<?php
if (!defined('ABSPATH')) exit;

class DCDC_DB {
    public static function tables() {
        global $wpdb;
        return array(
            'questions' => $wpdb->prefix . 'dcdc_questions',
            'answers'   => $wpdb->prefix . 'dcdc_answers',
            'categories'=> $wpdb->prefix . 'dcdc_categories',
            'participants' => $wpdb->prefix . 'dcdc_participants',
        );
    }

    public static function activate() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $t = self::tables();
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$t['questions']} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            campaign varchar(100) NOT NULL DEFAULT 'dia-das-criancas-2026',
            question_text text NOT NULL,
            question_type varchar(20) NOT NULL DEFAULT 'single',
            decade varchar(20) DEFAULT NULL,
            position int(11) unsigned NOT NULL DEFAULT 0,
            active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY campaign (campaign),
            KEY active (active)
        ) $charset;

        CREATE TABLE {$t['answers']} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            question_id bigint(20) unsigned NOT NULL,
            answer_text varchar(255) NOT NULL,
            points int(11) NOT NULL DEFAULT 0,
            position int(11) unsigned NOT NULL DEFAULT 0,
            active tinyint(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (id),
            KEY question_id (question_id)
        ) $charset;

        CREATE TABLE {$t['categories']} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            campaign varchar(100) NOT NULL DEFAULT 'dia-das-criancas-2026',
            name varchar(150) NOT NULL,
            min_points int(11) NOT NULL DEFAULT 0,
            max_points int(11) NOT NULL DEFAULT 9999,
            description text DEFAULT NULL,
            emoji varchar(20) DEFAULT NULL,
            position int(11) unsigned NOT NULL DEFAULT 0,
            active tinyint(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (id),
            KEY campaign (campaign),
            KEY points (min_points, max_points)
        ) $charset;

        CREATE TABLE {$t['participants']} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            campaign varchar(100) NOT NULL,
            name varchar(150) NOT NULL,
            score int(11) NOT NULL DEFAULT 0,
            category_id bigint(20) unsigned DEFAULT NULL,
            photo_id bigint(20) unsigned DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY campaign (campaign),
            KEY ranking (campaign, score),
            KEY status (status)
        ) $charset;";

        dbDelta($sql);
        self::ensure_seeded();
    }

    public static function ensure_seeded() {
        global $wpdb;
        $t = self::tables();

        $questions_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t['questions']}");
        if ($questions_count > 0) {
            return;
        }

        self::seed();
        update_option('dcdc_seeded', 1);
    }

    public static function seed() {
        global $wpdb;
        $t = self::tables();

        $questions_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t['questions']}");
        if ($questions_count > 0) {
            return;
        }

        $now = current_time('mysql');
        $campaign = 'dia-das-criancas-2026';

        $categories = array(
            array('🧸', 'Criança Digital', 0, 30, 'Sua infância já veio com Wi-Fi.', 1),
            array('🎮', 'Criança dos 2000', 31, 50, 'Você viveu entre o videogame, a TV e o computador.', 2),
            array('📺', 'Criança Raiz', 51, 70, 'Rua, televisão e brincadeira até anoitecer.', 3),
            array('🚲', 'Criança de Rua', 71, 85, 'Se sua mãe chamasse pelo nome completo, era hora de voltar.', 4),
            array('🏆', 'Lenda da Infância', 86, 100, 'Você não teve infância. Você teve uma aventura.', 5),
        );

        foreach ($categories as $c) {
            $wpdb->insert($t['categories'], array(
                'campaign'=>$campaign, 'emoji'=>$c[0], 'name'=>$c[1],
                'min_points'=>$c[2], 'max_points'=>$c[3], 'description'=>$c[4],
                'position'=>$c[5], 'active'=>1
            ));
        }

        $questions = array(
            array(
                'text'=>'Você já voltou para casa com o joelho ralado depois de brincar?',
                'type'=>'single', 'decade'=>'80/90', 'pos'=>1,
                'answers'=>array(
                    array('Sim',5), array('Não',0)
                )
            ),
            array(
                'text'=>'Qual dessas brincadeiras fazia parte da sua infância?',
                'type'=>'single', 'decade'=>'80/90', 'pos'=>2,
                'answers'=>array(
                    array('Pega-pega',8), array('Esconde-esconde',8),
                    array('Queimada',7), array('Jogar bola na rua',10)
                )
            ),
            array(
                'text'=>'Você teve algum videogame entre essas gerações?',
                'type'=>'single', 'decade'=>'90/2000', 'pos'=>3,
                'answers'=>array(
                    array('Super Nintendo',8), array('Mega Drive',8),
                    array('PlayStation',10), array('PlayStation 2',10)
                )
            ),
            array(
                'text'=>'Você já teve um Tamagotchi ou brinquedo eletrônico parecido?',
                'type'=>'single', 'decade'=>'90/2000', 'pos'=>4,
                'answers'=>array(
                    array('Sim',8), array('Não',0)
                )
            ),
            array(
                'text'=>'Qual dessas coisas fazia parte da sua rotina?',
                'type'=>'single', 'decade'=>'90/2000', 'pos'=>5,
                'answers'=>array(
                    array('TV e desenho',5), array('Locadora',8),
                    array('MSN',8), array('Lan house',10)
                )
            ),
        );

        foreach ($questions as $q) {
            $wpdb->insert($t['questions'], array(
                'campaign'=>$campaign, 'question_text'=>$q['text'],
                'question_type'=>$q['type'], 'decade'=>$q['decade'],
                'position'=>$q['pos'], 'active'=>1,
                'created_at'=>$now, 'updated_at'=>$now
            ));
            $qid = (int) $wpdb->insert_id;
            foreach ($q['answers'] as $i=>$a) {
                $wpdb->insert($t['answers'], array(
                    'question_id'=>$qid, 'answer_text'=>$a[0],
                    'points'=>$a[1], 'position'=>$i+1, 'active'=>1
                ));
            }
        }
    }
}
