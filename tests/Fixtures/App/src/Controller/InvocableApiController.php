<?php declare(strict_types=1);

namespace Condenast\BasicApiBundle\Tests\Fixtures\App\Controller;

use Condenast\BasicApiBundle\Annotation as Api;
use Condenast\BasicApiBundle\Tests\Fixtures\App\Entity\Note;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Routing\Annotation\Route;

class InvocableApiController
{
    /**
     * Get note
     *
     * @Route(
     *     "/notes/{id}",
     *     name="app.notes.get",
     *     methods={"GET"},
     *     requirements={"id": "\d+"}
     * )
     * @Api\Action(
     *     resourceName="Note",
     *     response=@Api\Response(
     *         type=Note::class,
     *         context={"groups": "note.detail"}
     *     )
     * )
     */
    public function __invoke()
    {
        return $this->createNote();
    }

    private function createNote(): Note
    {
        $note = new Note();
        $note->id = Uuid::fromString('a117aca5-a117-aca5-a117-aca5a117aca5');
        $note->title = 'Note about alpacas';
        $note->text = 'The alpaca is a species of South American camelid descended from the vicuña';
        $note->views = 47;

        return $note;
    }
}
