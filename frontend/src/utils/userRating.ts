export const getUserRating = (
    user: any,
    type?: string
) => {

    if (!user?.received_reviews?.length) {
        return null
    }

    let reviews = user.received_reviews
    if (type) {
        reviews = reviews.filter(
            (r: any) => r.type === type
        )
    }

    if (!reviews.length) {
        return null
    }

    const avg =
        reviews.reduce(
            (sum: number, r: any) => sum + r.rating,
            0
        ) / reviews.length

    return avg.toFixed(1)
}

export const getReviewsCount = (
    user: any,
    type?: string
) => {

    if (!user?.received_reviews?.length) {
        return 0
    }

    if (!type) {
        return user.received_reviews.length
    }

    return user.received_reviews.filter(
        (r: any) => r.type === type
    ).length
}