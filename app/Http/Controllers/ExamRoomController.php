<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExamRoomRequest;
use App\Http\Requests\UpdateExamRoomRequest;
use App\Models\ExamRoom;
use App\Services\ExamRoomService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExamRoomController extends Controller
{
    public function __construct(
        protected ExamRoomService $examRoomService
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ExamRoom::class);

        $rooms = ExamRoom::query()->with('appointments')->paginate(15)->withQueryString();

        return Inertia::render('ExamRooms/Index', [
            'rooms' => $rooms,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', ExamRoom::class);

        return Inertia::render('ExamRooms/Create');
    }

    public function store(StoreExamRoomRequest $request): RedirectResponse
    {
        $room = $this->examRoomService->createRoom($request->validated());

        return redirect()->route('exam-rooms.show', $room)
            ->with('success', 'Exam room created successfully.');
    }

    public function show(ExamRoom $examRoom): Response
    {
        $this->authorize('view', $examRoom);

        $examRoom->load('appointments.patient', 'appointments.user');

        return Inertia::render('ExamRooms/Show', [
            'room' => $examRoom,
        ]);
    }

    public function update(UpdateExamRoomRequest $request, ExamRoom $examRoom): RedirectResponse
    {
        $this->examRoomService->updateRoom($examRoom, $request->validated());

        return redirect()->route('exam-rooms.show', $examRoom)
            ->with('success', 'Exam room updated successfully.');
    }

    public function activate(ExamRoom $examRoom): RedirectResponse
    {
        $this->authorize('activate', $examRoom);

        $this->examRoomService->activateRoom($examRoom);

        return redirect()->route('exam-rooms.show', $examRoom)
            ->with('success', 'Exam room activated successfully.');
    }

    public function deactivate(ExamRoom $examRoom): RedirectResponse
    {
        $this->authorize('deactivate', $examRoom);

        $this->examRoomService->deactivateRoom($examRoom);

        return redirect()->route('exam-rooms.show', $examRoom)
            ->with('success', 'Exam room deactivated successfully.');
    }
}
