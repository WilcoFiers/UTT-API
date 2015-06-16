<?php
/**
 * Created by PhpStorm.
 * User: markmooibroek
 * Date: 26/05/15
 * Time: 11:01
 */

namespace app\Http\Controllers\v1;


use App\Http\Controllers\Controller;
use App\Models\Assertion;
use App\Models\Assertor;
use App\Models\Evaluation;
use App\Models\LDModel;
use App\Models\Webpage;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;

class EvaluationController extends Controller
{

    public function listAction()
    {
        return [];
    }

    public function getAction($id)
    {
        return Evaluation::find($id);
    }

    public function createAction()
    {
        $model = new Evaluation();
        $input = Input::all();

        $validator = $model->validateInput($input);

        if ($validator->fails()) {
            app()->abort(422, $validator->errors()->first());
        }


        $assertion = new Assertion([
            "date" => new Carbon,
            "mode" => Input::get("auditResult.mode"),
            "test_id" => Input::get("auditResult.test.@id"),
            "test_type" => Input::get("auditResult.test.@type"),
            "result_type" => Input::get("auditResult.result.@type"),
            "result_outcome" => Input::get("auditResult.result.outcome")
        ]);

        DB::transaction(function () use ($model, $assertion) {

            /** @var Assertor $assertor */
            $assertor = Assertor::find(LDModel::getIdFromLdId(Input::get("creator.@id")));

            $model->fill(["date" => new Carbon]);
            $model->creator()->associate($assertor);
            $model->save();

            /** @var Webpage $subject */
            $subject = Webpage::find(LDModel::getIdFromLdId(Input::get("auditResult.subject")));

            $assertion->assertor()->associate($assertor);
            $assertion->subject()->associate($subject);
            $assertion->evaluation()->associate($model);

            $assertion->save();
        });

        return $model;
    }


    public function addAction($id)
    {

        /** @var Evaluation $evaluation */
        $evaluation = Evaluation::find($id);
        if (!$evaluation) {
            app()->abort(422, "Parent evaluation not found");
        }

        $model = new Assertion();
        $input = Input::all();

        $validator = $model->validateInput($input);

        if ($validator->fails()) {
            app()->abort(422, $validator->errors()->first());
        }

        $model->fill([
            "date" => new Carbon,
            "mode" => Input::get("mode"),
            "test_id" => Input::get("test.@id"),
            "test_type" => Input::get("test.@type"),
            "result_type" => Input::get("result.@type"),
            "result_outcome" => Input::get("result.outcome"),
            "subject_id" => LDModel::getIdFromLdId(Input::get("subject")),
            "asserted_by" => LDModel::getIdFromLdId(Input::get("assertedBy.@id"))
        ]);


        $model->evaluation()->associate($evaluation);

        $model->save();

        return $model;
    }
}