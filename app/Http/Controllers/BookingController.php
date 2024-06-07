<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Arena;
use App\Models\Booking;
use Illuminate\Http\Request;
use App\Http\Requests\Admin\BookingRequest;

class BookingController extends Controller
{

    public $sources = [
        [
            'model'      => Booking::class,
            'date_field' => 'time_from',
            'date_field_to' => 'time_to',
            'field'      => 'user_id',
            'number'      => 'arena_id',
            'prefix'     => '',
            'suffix'     => '',
        ],
    ];

    public function index(Request $request)
    {

        $bookings = [];
        $arenas = Arena::where('status', 1)->get();

        foreach ($this->sources as $source) {
            $models = $source['model']::where('status', '0')
                ->get();
            foreach ($models as $model) {
                $crudFieldValue = $model->getOriginal($source['date_field']);
                $crudFieldValueTo = $model->getOriginal($source['date_field_to']);
                $arena = Arena::findOrFail($model->getOriginal($source['number']));
                $user = User::findOrFail($model->getOriginal($source['field']));
                $timeBreak = \Carbon\Carbon::parse($crudFieldValueTo)->format('H:i');


                if (!$crudFieldValue && $crudFieldValueTo) {
                    continue;
                }

                $bookings[] = [
                    'title' => trim($source['prefix'] . "($arena->number)" . $user->name
                        . " ") . " " . $timeBreak,
                    'start' => $crudFieldValue,
                    'end' => $crudFieldValueTo,
                ];
            }
        }

        return view('welcome', compact('arenas', 'bookings'));
    }

    public function booking(Request $request)
    {

        $arenas = Arena::where('status', 1)->get();
        $arenaNumber = $request->get('number');

        return view('booking', compact('arenas', 'arenaNumber'));
    }

    public function store(BookingRequest $request)
    {
        // Retrieve the arena details
        $arena = Arena::findOrFail($request->arena_id);

        // Calculate the order date and payment due date
        $orderDate = date('Y-m-d H:i:s');
        $paymentDue = (new \DateTime($orderDate))->modify('+1 hour')->format('Y-m-d H:i:s');

        // Calculate the duration in hours between time_from and time_to
        $timeFrom = new \DateTime($request->time_from);
        $timeTo = new \DateTime($request->time_to);
        $interval = $timeFrom->diff($timeTo);

        // Calculate the total duration in hours (including fractional hours)
        $hours = $interval->h + ($interval->i / 60);

        // Calculate the grand total based on the duration and arena price per hour
        $pricePerHour = $arena->price;
        $grandTotal = $hours * $pricePerHour;


        // Set default value for bukti_pembayaran
        $buktiPembayaran = $request->bukti_pembayaran ?? 'Default Value'; // Change 'Default Value' to whatever suits your needs

        // Create the booking
        $booking = Booking::create($request->validated() + [
            'user_id' => auth()->id(),
            'grand_total' => $arena->price,
            'status' => $request->status ?? 0,
            'bukti_pembayaran' => $buktiPembayaran
        ]);

        // Redirect to success page
        return redirect()->route('booking.success', [$paymentDue])->with([
            'message' => 'Terimakasih sudah booking!',
            'alert-type' => 'success'
        ]);
    }

    public function success($paymentDue)
    {
        $booking = Booking::where('user_id', auth()->id())->latest()->first();
        return view('success', compact('paymentDue', 'booking'));
    }

    public function storeBuktiPembayaran(Request $request, $bookingId)
    {
        // Validate the request
        $request->validate([
            'bukti_pembayaran' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // Adjust file size as needed
        ]);

        // Find the booking
        $booking = Booking::findOrFail($bookingId);

        // Handle the file upload
        if ($request->hasFile('bukti_pembayaran')) {
            $file = $request->file('bukti_pembayaran');
            $fileName = $file->getClientOriginalName();

            // Store the image in storage/app/public/img folder
            $filePath = $file->storeAs('img', $fileName, 'public'); //save image

            // Update the booking with the payment proof path
            $booking->bukti_pembayaran = $filePath;
            $booking->save();
        }

        // Redirect back with success message
        return redirect()->route('admin.bookings.show', $booking->id)->with([
            'message' => 'Bukti pembayaran berhasil diunggah!',
            'alert-type' => 'success'
        ]);
    }
}
