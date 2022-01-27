<?php

function quizmaker_show_form(){

    $user = wp_get_current_user();
    $allowed_roles = array( 'administrator' ); //    $allowed_roles = array( 'editor', 'administrator', 'author' );
    if ( array_intersect( $allowed_roles, $user->roles ) ) {

        // Generate a custom nonce value.
        $hpx_add_quiz_nonce = wp_create_nonce( 'hpx_add_quiz_meta_form_nonce' );

        global $wpdb;

        $quiz_categories_table      =   $wpdb->prefix . 'aysquiz_quizcategories';
        $quizes_table               =   $wpdb->prefix . 'aysquiz_quizes';
        $questions_table            =   $wpdb->prefix . 'aysquiz_questions';
        $question_categories_table  =   $wpdb->prefix . 'aysquiz_categories';
        $answers_table              =   $wpdb->prefix . 'aysquiz_answers';
        $reports_table              =   $wpdb->prefix . 'aysquiz_reports';
        $rates_table                =   $wpdb->prefix . 'aysquiz_rates';
        $themes_table               =   $wpdb->prefix . 'aysquiz_themes';
        $settings_table             =   $wpdb->prefix . 'aysquiz_settings';

        $sqlQuizCategoriesTable = "SELECT id, title FROM {$quiz_categories_table}";
        $sqlQuestCategoriesTable = "SELECT id, title FROM {$question_categories_table}";
        $resQuizCategoriesTable = $wpdb->get_results( $sqlQuizCategoriesTable );
        $resQuestCategoriesTable = $wpdb->get_results( $sqlQuestCategoriesTable );


        /*
        $s = "Select * from EYTXGc_aysquiz_questions where id not in 
(
SELECT
		SUBSTRING_INDEX(SUBSTRING_INDEX(EYTXGc_aysquiz_quizes.question_ids, ',', numbers.n), ',', -1) FragenIDS
	FROM
		(SELECT 1 n UNION ALL SELECT 2
		UNION ALL SELECT 3 UNION ALL SELECT 4) numbers INNER JOIN EYTXGc_aysquiz_quizes
		ON CHAR_LENGTH(EYTXGc_aysquiz_quizes.question_ids)
		-CHAR_LENGTH(REPLACE(EYTXGc_aysquiz_quizes.question_ids, ',', ''))>=numbers.n-1
		where ID = 2
)";




        $res = $wpdb->get_results( $ss );



        echo '<pre>';
        //var_dump($resQuizCategoriesTable);

        echo '</pre>';
*/



        global $post;


        ob_start();
        ?>


    <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post" id="quiz_enhancement_form">
        <input type="hidden" name="hpx_add_quiz_nonce" value="<?php echo $hpx_add_quiz_nonce ?>" />
        <input type="hidden" name="action" value="hpx_enhancement">


        <div class="hpx_formheader">
            <div>
                <label for="quizkategorie">Quizkategorie wählen:</label>
                <select name="quizkategorie">
                    <?php foreach($resQuizCategoriesTable as $QuizCategoriesTableRow) { ?>
                        <option value="<?php echo $QuizCategoriesTableRow->id ?>" <?php echo  ($_GET['quizkategorie'] == $QuizCategoriesTableRow->id ? 'selected="selected"' : "") ?>><?php echo $QuizCategoriesTableRow->title ?></option>
                    <?php }?>
                </select>
            </div>
            <div>
                <label for="fragenkategorie">Fragenkategorie wählen:</label>
                <select name="fragenkategorie">
                    <option value="-1">alle Fragenkategorien</option>
                    <?php foreach($resQuestCategoriesTable as $QuestCategoriesTableRow) { ?>
                        <option value="<?php echo $QuestCategoriesTableRow->id ?>" <?php echo  ($_GET['fragenkategorie'] == $QuestCategoriesTableRow->id ? 'selected="selected"' : "") ?>><?php echo $QuestCategoriesTableRow->title ?></option>
                    <?php }?>
                </select>
            </div>
            <div>
                <label for="gestellt">Zeige gestellte Fragen:</label>
                <input type="checkbox" id="scales" name="gestellt" <?php echo  ($_GET['gestellt'] == "on" ? "checked" : "")?>>
            </div>
            <div>
                <p>&nbsp;</p>
            </div>

            <div>
                <button type="submit" class="btn btn-primary">Fragen anzeigen</button>
            </div>
            <div>
                <p>&nbsp;</p>
            </div>
        </div>
    </form>
    <?php


        if ($_GET['quizkategorie'] !== null && $_GET['fragenkategorie'] !== null){
            $quizkategorie = $_GET['quizkategorie'];
            $fragenkategorie = $_GET['fragenkategorie'];
            $gestellt = isset($_GET['gestellt'] );

            $key = array_search($quizkategorie, array_column($resQuizCategoriesTable, 'id'));
            echo "<h1>Quizkategorie:" . $resQuizCategoriesTable[$key]->title . "</h1>";


            //zeige gestellte Fragen: = true
            //-> zeige quize (wie jetzt)
            //->zeige Tabelle fragen pro quiz (dann Fragenkategorien beachten)
            //-> nicht getsellte kommen dann nicht

            $sqlQuizTable = "SELECT * FROM {$quizes_table} where quiz_category_id={$quizkategorie}";
            $resQuizTable = $wpdb->get_results( $sqlQuizTable );

            $usedQuestions = [];
            foreach($resQuizTable as $QuizTableRow) {
                if($gestellt) {
                    echo "<div class = 'hpx_quizresult'>";
                    echo "<h2>Quiz: " . $QuizTableRow->title . "</h2>";
                    echo getQuestionstable($QuizTableRow->question_ids, $fragenkategorie, 'in');
                }
                $usedQuestions = array_merge($usedQuestions, explode(",", $QuizTableRow->question_ids));
            }

            if(!$gestellt) {
                echo "<h2>nicht gestellte Fragen</h2>";
                $usedQuestions = array_unique($usedQuestions, SORT_NUMERIC);
                $idsSQL = implode(',', array_map('intval', $usedQuestions));

                echo getQuestionstable($idsSQL, $fragenkategorie, 'not in');
            }

        }
	}
    else {
        ?>
        <p>
            <?php
                wp_die( __( 'Bitte im Backend anmelden', "Quizerweiterung" ), __( 'Error', "Quizerweiterung" ), array(
                'response' 	=> 403,
                'back_link' => 'admin.php?page=' . "Quizerweiterung",
            ) );
        ?> </p>
        <?php

    }

    $content = ob_get_contents();
    ob_end_clean();
    return $content;
}

//Hooks
add_shortcode( 'hpx_quizmaker_show_form', 'quizmaker_show_form' );


function getQuestionstable( $usedQuestions, $questionCategory, $in){
    global $wpdb;

    $questions_table            =   $wpdb->prefix . 'aysquiz_questions';
    $question_categories_table  =   $wpdb->prefix . 'aysquiz_categories';

    $sqlQuestionsTable = "select q.id, q.category_id, q.question, c.title cat_title from {$questions_table} q inner join {$question_categories_table} c on q.category_id = c.id where q.id {$in} ({$usedQuestions})";

    if ($questionCategory > -1 ){
        $sqlQuestionsTable .= " and q.category_id = {$questionCategory}";
    }

    $resQuestionsTable = $wpdb->get_results( $sqlQuestionsTable );

    $result = "<table>";
    $result .= "<tr>
                    <th>ID</th>
                    <th>Frage-Kategorie</th>
                    <th>Frage</th>
                  </tr>";
    foreach($resQuestionsTable as $QuestionsTableRow) {
        $result .= "<tr>
                        <td>{$QuestionsTableRow->id}</td>
                        <td>{$QuestionsTableRow->cat_title}</td>
                        <td>{$QuestionsTableRow->question}</td>
                      </tr>";
    }
    $result .= "</table>";

    return $result;

}


function post_admin_hpx_enhancement(){
    if( isset( $_POST['hpx_add_quiz_nonce'] ) && wp_verify_nonce( $_POST['hpx_add_quiz_nonce'], 'hpx_add_quiz_meta_form_nonce') ) {
        //$nds_user_meta_key = sanitize_key( $_POST['nds']['user_meta_key'] );
        status_header(200);
        custom_redirect($_POST );
    }
    else {
        wp_die( __( 'Invalid nonce specified', "Quizerweiterung" ), __( 'Error', "Quizerweiterung" ), array(
            'response' 	=> 403,
            'back_link' => 'admin.php?page=' . "Quizerweiterung",
        ) );
    }
}



add_action( 'admin_post_hpx_enhancement', 'post_admin_hpx_enhancement' );
add_action( 'admin_post_nopriv_hpx_enhancement', 'post_admin_hpx_enhancement' ); // this is for non logged users




function custom_redirect($response ) {

    if ($refUrl = parse_url($_SERVER["HTTP_REFERER"])) {

        if( isset( $response['quizkategorie'] )){
            $qkat = $response['quizkategorie'];
        }
        if( isset( $response['fragenkategorie'] )){
            $questkat = $response['fragenkategorie'];
        }
        if( isset( $response['gestellt'] )){
            $gestellt = $response['gestellt'];
        }


        $url = esc_url_raw( add_query_arg( array(
                                            'quizkategorie' => $qkat,
                                            'fragenkategorie' => $questkat,
                                            'gestellt' => $gestellt,
                                                ),
          sprintf('%s://%s%s', $refUrl['scheme'], $refUrl['host'], $refUrl['path'])
        ) ) ;

        wp_redirect( $url, 302, 'WordPress' );
    }
}
