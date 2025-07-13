<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\OrdersLaundries;
use App\Models\ProfilePelanggan;
use App\Http\Requests\AddCommentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CommentController extends Controller
{
    public function addComment(AddCommentRequest $request)
    {
        try {
            $user = Auth::user();

            $profilePelanggan = ProfilePelanggan::where('user_id', $user->user_id)->first();
            if (!$profilePelanggan) {
                return response()->json([
                    'message' => 'Profil pelanggan tidak ditemukan.',
                    'status_code' => 404,
                    'data' => null
                ], 404);
            }

            $order = OrdersLaundries::where('id', $request->order_id)
                                    ->where('id_profile', $profilePelanggan->id_profile)
                                    ->first();

            if (!$order) {
                return response()->json([
                    'message' => 'Pesanan tidak ditemukan atau bukan milik Anda.',
                    'status_code' => 404,
                    'data' => null
                ], 404);
            }
            Log::info('Checking order status for order_id: ' . $order->id . ' Status: ' . $order->status);
            if ($order->status !== 'Selesai') {
                return response()->json([
                    'message' => 'Komentar hanya bisa ditambahkan untuk pesanan yang sudah selesai.',
                    'status_code' => 400,
                    'data' => null
                ], 400);
            }

            if (Comment::where('order_id', $request->order_id)->exists()) {
                return response()->json([
                    'message' => 'Anda sudah memberikan komentar untuk pesanan ini.',
                    'status_code' => 409,
                    'data' => null
                ], 409);
            }

            $comment = Comment::create([
                'order_id' => $request->order_id,
                'user_id' => $user->user_id,
                'comment_text' => $request->comment_text,
                'rating' => $request->rating,
            ]);

            return response()->json([
                'message' => 'Komentar berhasil ditambahkan.',
                'status_code' => 201,
                'data' => $comment 
            ], 201);

        } catch (\Exception $e) {
            Log::error("Error adding comment: " . $e->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }
     public function getCommentByOrderId(int $orderId)
    {
        try {
            $comment = Comment::with(['order.profile', 'user'])
                              ->where('order_id', $orderId)
                              ->first(); 

            if (!$comment) {
                return response()->json([
                    'message' => 'Komentar untuk pesanan ID ' . $orderId . ' tidak ditemukan.',
                    'status_code' => 404,
                    'data' => null
                ], 404);
            }

            return response()->json([
                'message' => 'Komentar ditemukan.',
                'status_code' => 200,
                'data' => [
                    'id' => $comment->id,
                    'order_id' => $comment->order_id,
                    'order_status' => $comment->order->status,
                    'customer_profile_name' => $comment->order->profile->name,
                    'comment_text' => $comment->comment_text,
                    'rating' => $comment->rating,
                    'created_at' => $comment->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $comment->updated_at->format('Y-m-d H:i:s'),
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error("Error getting comment by order ID for admin: " . $e->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }
    public function getMyComments()
    {
        try {
            $user = Auth::user();
            $comments = Comment::with(['order.profile', 'user'])
                              ->where('user_id', $user->user_id)
                              ->get()
                              ->map(function ($comment) {
                                  return [
                                      'id' => $comment->id,
                                      'order_id' => $comment->order_id,
                                      'order_status' => $comment->order->status,
                                      'customer_profile_name' => $comment->order->profile->name,
                                      'comment_text' => $comment->comment_text,
                                      'rating' => $comment->rating,
                                      'created_at' => $comment->created_at->format('Y-m-d H:i:s'),
                                      'updated_at' => $comment->updated_at->format('Y-m-d H:i:s'),
                                  ];
                              });

            if ($comments->isEmpty()) {
                return response()->json([
                    'message' => 'Tidak ada komentar yang ditemukan untuk Anda.',
                    'status_code' => 404,
                    'data' => []
                ], 404);
            }

            return response()->json([
                'message' => 'Daftar komentar Anda.',
                'status_code' => 200,
                'data' => $comments
            ], 200);

        } catch (\Exception $e) {
            Log::error("Error getting my comments: " . $e->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }
}