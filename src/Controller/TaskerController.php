<?php
namespace Drupal\tasker\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;

use Drupal\user\Entity\User;


/**
 * Class TaskerController.
 */
class TaskerController extends ControllerBase
{


    /**
     *
     * @return array
     *
     */
    public function list_task()
    {

        //On récupére les taches en cours ou ouverte
        $nodes = $this->todo();



        //classe les taches dans un tableaux en fonction du mail du responsable de la tache

        $liste_tache = array();

        foreach ($nodes as $index => $node) {

          //On charge les utilisateurs cocernés
            $user = User::load($node->field_responsable->target_id);

          //tableau classé
          $liste_tache[$user->mail->value].= $node->title->value." -- tache = ".$node->field_etat->value."\r\n";

         }

        //on boucle sur le tableau pour envoyer le mail avec la liste des taches
        foreach ($liste_tache as $mail => $tache) {

            $this->send_by_mail($mail,$tache);

        }

        return array('#markup' => 'taches envoyées');


    }

    /**
     * liste les taches ouvertes et en cours inférieur à la date du jour
     *
     * @return mixed
     *
     *
     */
    public function todo()
    {

        $query = \Drupal::entityQuery('node');

        $condition_and = $query->andConditionGroup();
        $condition_and->condition('field_etat', array('ouvert', 'encours'), 'IN');


        //$query->condition('field_echeance',  date("Y-m-d"), '<');
        $query->condition($condition_and);
        $results = $query->execute();

        foreach ($results as $index => $result) {

            $node[$index] = Node::load($result);

        }
        return $node;
    }


    /**
     *  Gestion des envois des emails

     * @param $to
     * @param $task
     *
     */
    public function send_by_mail($to, $task) {

        $mailManager = \Drupal::service('plugin.manager.mail');
        $module = 'tasker';
        $key = 'task_notifier';

        $params['message'] = $task;
        $params['title'] = t('liste de vos taches');
        $langcode = \Drupal::currentUser()->getPreferredLangcode();
        $send = true;

        $result = $mailManager->mail($module, $key, $to, $langcode, $params, 'contact@ewok-studio.fr', $send);


        if ($result['result'] != true) {
            $message = t('There was a problem sending your email notification to @email.', array('@email' => $to));
            drupal_set_message($message, 'error');
            \Drupal::logger('mail-log')->error($message);
            return;
        }

        $message = t('An email notification has been sent to @email ', array('@email' => $to));
        drupal_set_message($message);
        \Drupal::logger('mail-log')->notice($message);
    }


}
